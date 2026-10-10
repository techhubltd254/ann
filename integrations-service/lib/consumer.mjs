// SQL-backed, at-least-once consumer. Checkpoints represent completed fetched IDs,
// not contiguous integers: auto-increment IDs can have gaps and topic filters skip IDs.
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { ROOT, log } from './core.js';
import * as sql from './bus-sql.mjs';
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const say=m=>log.info(m),warn=m=>log.warn(m);
export const idemKey=s=>crypto.createHash('sha1').update(String(s)).digest('hex');
const globToRe=p=>new RegExp('^'+String(p).replace(/[.+?^${}()|[\]\\]/g,'\\$&').replace(/\*/g,'.*')+'$');
export class Consumer {
 constructor(opts={}){
  if(typeof opts.handler!=='function')throw Error('Consumer requires a handler function');
  this.group=opts.group||'consumer';this.topicNames=opts.topics||['*'];this.topics=this.topicNames.map(globToRe);this.handler=opts.handler;
  this.store=opts.store||sql;this.mode=opts.mode||'unspecified';this.retryNotBefore=0;this.maxAttempts=opts.maxAttempts??Number(process.env.CONSUMER_MAX_ATTEMPTS||3);
  this.backoffMs=opts.backoffMs??Number(process.env.CONSUMER_BACKOFF_MS||20);this.pollMs=opts.pollMs??250;
  this.maxInFlight=opts.maxInFlight??Number(process.env.CONSUMER_MAX_IN_FLIGHT||8);this.onIdle=opts.onIdle||null;
  this.stateDir=opts.stateDir||path.join(ROOT,'run','offsets',this.group);fs.mkdirSync(this.stateDir,{recursive:true});
  this.ackOffset=0;this.readOffset=0;this.ackOffsetLoaded=false;this.terminal=new Set();this.pendingAcked=new Set();this.fetchedOrder=[];this.inflight=new Map();
  this.stopping=false;this.loopP=null;this.startedAt=null;this.checkpointDirty=false;this.sqlAvailable=false;this.baseProcessed=0;this.baseDlq=0;this._pendingRows=null;
  this.stats={events_in:0,filtered:0,deduped:0,processed:0,acked:0,retried:0,dlq:0,contract_errors:0,peak_inflight:0,restarts:0,read_errors:0,persistence_errors:0};
 }
 get lag(){return this._pendingRows;}
 get inflightCount(){return this.inflight.size;}
 metrics(){return {group:this.group,mode:this.mode,topics:this.topicNames,ack_offset:this.ackOffset,read_offset:this.readOffset,lag:this.lag,sql_available:this.sqlAvailable,checkpoint_pending:this.checkpointDirty,inflight:this.inflight.size,terminal_keys:this.terminal.size,stopping:this.stopping,uptime_s:this.startedAt?Math.round((Date.now()-this.startedAt)/1000):0,...this.stats};}
 async start({handleSignals=true}={}){
  if(this.loopP)return this;this.stopping=false;this.startedAt=Date.now();
  try{const ck=await this.store.loadOffset(this.group);this.ackOffset=Number(ck.ack_offset||0);this.baseProcessed=Number(ck.processed_count||0);this.baseDlq=Number(ck.dlq_count||0);this.ackOffsetLoaded=true;this.sqlAvailable=true;}
  catch(e){this.stats.read_errors++;warn(`consumer ${this.group}: SQL checkpoint unavailable; processing paused`);}
  this.readOffset=this.ackOffset;
  if(handleSignals){for(const sig of ['SIGTERM','SIGINT'])process.on(sig,async()=>{try{await this.stop();process.exit(0);}catch(e){warn(e.message);process.exit(1);}});}
  this.loopP=this.#loop();return this;
 }
 async stop(){if(!this.loopP)return this.metrics();this.stopping=true;await this.loopP;this.loopP=null;await this.#persist();return this.metrics();}
 async #loadCheckpoint(){const ck=await this.store.loadOffset(this.group);this.ackOffset=Number(ck.ack_offset||0);this.readOffset=this.ackOffset;this.baseProcessed=Number(ck.processed_count||0);this.baseDlq=Number(ck.dlq_count||0);this.ackOffsetLoaded=true;this.sqlAvailable=true;}
 async #loop(){
  while(!this.stopping||this.inflight.size){
   if(this.stopping){await sleep(5);continue;}
   if(Date.now()<this.retryNotBefore){await sleep(Math.min(100,this.retryNotBefore-Date.now()));continue;}
   try{
    if(!this.ackOffsetLoaded)await this.#loadCheckpoint();
    if(this.checkpointDirty)await this.#persist();
    if(this.inflight.size){await sleep(5);continue;}
    const {rows}=await this.store.fetchEventsSince(this.ackOffset,this.maxInFlight,this.topicNames);
    this.sqlAvailable=true;
    if(!rows.length){this._pendingRows=await this.store.countPendingEvents(this.ackOffset,this.topicNames);if(this.onIdle)await this.onIdle();await sleep(this.pollMs);continue;}
    let previous=this.ackOffset;
    for(const row of rows){const offset=Number(row.id);if(!Number.isSafeInteger(offset)||offset<=previous)throw Error('Invalid or unordered SQL event ID');previous=offset;if(!this.fetchedOrder.includes(offset))this.fetchedOrder.push(offset);this.fetchedOrder.sort((a,b)=>a-b);this.readOffset=Math.max(this.readOffset,offset);this.#dispatch({offset,ev:this.#normalizeEvent(row)});}
    this._pendingRows=await this.store.countPendingEvents(this.ackOffset,this.topicNames);
    await sleep(0);
   }catch(e){this.sqlAvailable=false;this.stats.read_errors++;await sleep(Math.max(this.pollMs,250));}
  }
 }
 #normalizeEvent(row){return {topic:row.topic,payload:row.payload||{},id:`ev-${row.id}`,idempotencyKey:row.idempotency_key,correlationId:row.correlation_id,causationId:row.causation_id,hop:row.hop||0,key:String(row.id),ts:row.published_at};}
 #dispatch({offset,ev}){
  if(offset<=this.ackOffset||this.inflight.has(offset))return;this.stats.events_in++;
  if(!this.topics.some(re=>re.test(ev.topic||''))){this.stats.filtered++;this.#ack(offset);return;}
  const key=ev.idempotencyKey||`line-${offset}`;
  if(this.terminal.has(key)){this.stats.deduped++;this.#ack(offset);return;}
  const p=this.#attempt(ev,key,offset);this.inflight.set(offset,p);this.stats.peak_inflight=Math.max(this.stats.peak_inflight,this.inflight.size);
  p.catch(()=>{this.sqlAvailable=false;this.stats.persistence_errors++;this.retryNotBefore=Date.now()+Math.max(this.pollMs,1000);}).finally(()=>this.inflight.delete(offset));
 }
 async #attempt(ev,key,offset){
  if(await this.store.hasEventKey(key,this.group)){this.stats.deduped++;this.terminal.add(key);this.#ack(offset);return;}
  let lastErr;
  for(let a=1;a<=this.maxAttempts;a++){
   if(a>1){this.stats.retried++;await sleep(this.backoffMs*2**(a-2));}
   try{
    await this.handler(ev,{group:this.group,attempt:a,offset});
    await this.store.insertEventKey(key,this.group,offset);
    this.stats.processed++;this.stats.acked++;this.terminal.add(key);this.#ack(offset);return;
   }catch(e){lastErr=e;}
  }
  await this.store.insertDlq(this.group,ev.topic,key,offset,String(lastErr?.message||lastErr),this.maxAttempts,ev);
  this.stats.dlq++;if(/contract/i.test(String(lastErr?.message)))this.stats.contract_errors++;
  this.terminal.add(key);this.#ack(offset);
 }
 #ack(offset){
  this.pendingAcked.add(offset);
  while(this.fetchedOrder.length&&this.pendingAcked.has(this.fetchedOrder[0])){const id=this.fetchedOrder.shift();this.pendingAcked.delete(id);this.ackOffset=Math.max(this.ackOffset,id);this.checkpointDirty=true;}
 }
 async #persist(){
  if(!this.checkpointDirty)return;
  const offset=this.ackOffset;
  try{await this.store.updateOffset(this.group,offset,this.baseProcessed+this.stats.processed,this.baseDlq+this.stats.dlq);this.sqlAvailable=true;}
  catch(e){this.stats.persistence_errors++;this.sqlAvailable=false;throw e;}
  this._saveLocalCheckpoint(offset);this.checkpointDirty=this.ackOffset!==offset;
 }
 _loadLocalCheckpoint(){try{return Number(JSON.parse(fs.readFileSync(path.join(this.stateDir,'checkpoint.json'),'utf8')).ackOffset)||0;}catch{return 0;}}
 _saveLocalCheckpoint(offset=this.ackOffset){const target=path.join(this.stateDir,'checkpoint.json'),tmp=target+`.${process.pid}.tmp`;fs.writeFileSync(tmp,JSON.stringify({group:this.group,ackOffset:offset,updatedAt:new Date().toISOString()}));fs.renameSync(tmp,target);}
 async readDlq(limit=100){return this.store.fetchDlq(this.group,limit);}
}

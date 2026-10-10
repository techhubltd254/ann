import assert from 'node:assert/strict';import fs from 'node:fs';import path from 'node:path';
import {validateLiveAlgorithmScore} from './integrations-service/consumers/algorithms-consumer.mjs';
import {validateLiveModelScore} from './integrations-service/consumers/ml-engine-consumer.mjs';
const checks=[];function check(name,fn){fn();checks.push({name,pass:true});console.log('PASS',name);}
for(const [name,parse] of [['algorithm',validateLiveAlgorithmScore],['model',validateLiveModelScore]]){
 for(const n of [0,0.5,1])check(name+'-accepts-valid-score:'+n,()=>assert.equal(parse({score:n}),n));
 for(const [label,v] of [['missing',undefined],['null',null],['string','0.5'],['negative',-1],['above-one',1.01],['NaN',NaN],['Infinity',Infinity]])check(name+'-rejects:'+label,()=>assert.throws(()=>parse({score:v})));
}
check('algorithm-accepts-explicit-edge-score',()=>assert.equal(validateLiveAlgorithmScore({edge_score:0}),0));
// Instrument a disposable COPY of the SQL module. No production pool or table access.
const sqlPath=path.resolve('./integrations-service/lib/bus-sql.mjs');let s=fs.readFileSync(sqlPath,'utf8');s=s.replace("import mysql from 'mysql2/promise';","const mysql={createPool:()=>globalThis.__isolatedSQLFixturePool};");
const tmp=path.resolve('./integrations-service/lib/sql-fixture-only.mjs');fs.writeFileSync(tmp,s);let fail=true;const queries=[];globalThis.__isolatedSQLFixturePool={execute:async(q,args)=>{queries.push({q,args});if(fail)throw Error('disposable SQL failure');if(q.includes('COUNT(*)'))return [[{c:2}]];return [[],{}];}};
const sql=await import('file://'+tmp);
for(const [name,args] of [['loadOffset',['isolated']],['hasEventKey',['key','isolated']],['insertEventKey',['key','isolated',7]],['updateOffset',['isolated',7,1,0]],['insertDlq',['isolated','wanted','key',7,'fixture',3,{}]],['insertAlgorithmResult',[1,0.5,'fixture',0,'fixture']],['insertPrediction',[1,0.5,'medium',0.5,'fixture']],['countPendingEvents',[0,['wanted']]]]){await assert.rejects(sql[name](...args));checks.push({name:'authoritative-SQL-error-propagates:'+name,pass:true});}
fail=false;await sql.updateOffset('isolated',7,1,0);check('checkpoint-SQL-update-cannot-regress',()=>assert.ok(queries.at(-1).q.includes('GREATEST(ack_offset, VALUES(ack_offset))')));
await sql.countPendingEvents(7,['pipeline.*','exact_name']);check('pending-count-is-topic-and-offset-scoped',()=>{const q=queries.at(-1);assert.ok(q.q.includes('id > ?')&&q.q.includes("LIKE ? ESCAPE '='"));assert.deepEqual(q.args,[7,'pipeline.%','exact_name']);});
fs.unlinkSync(tmp);delete globalThis.__isolatedSQLFixturePool;fs.writeFileSync('sql-score-fixture-verification.json',JSON.stringify({checkedAt:new Date().toISOString(),productionDatabaseAccess:false,checks,pass:true},null,2));console.log('SQL_SCORE_FIXTURES_PASS',checks.length);

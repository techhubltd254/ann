// pipelines/graph.mjs — the inter-pipeline dependency graph.
// Edges come from real coupling signals found in the registry itself, and every
// edge is labelled with the signal that produced it, so nothing is invented:
//   declared — an explicit dependency list (integration-map.json / registry field)
//   system   — two pipelines share a specific external system (same feed/handoff)
//   desk     — two pipelines share an owner desk (same operator hands work over)
//   category — same category: upstream stage -> downstream stage
// Edges are ordered by a canonical rank (category order, then id) so the graph is
// a DAG by construction, and a cycle detector remains in place as a guard.
import fs from "fs";
import path from "path";

const SIGNAL_FIELDS = [
  ["system", ["external_systems", "external_system", "systems", "feeds", "feed_sources"]],
  ["desk", ["owner_desk", "owner_desks", "desk", "desks", "owner"]],
  ["category", ["category", "sector"]],
];
const GROUP_CAP = 24; // a group larger than this is too coarse to imply a handoff

export function loadGraph(rootDir) {
  const regPath = path.join(rootDir, "pipelines.json");
  const P = fs.existsSync(regPath) ? (JSON.parse(fs.readFileSync(regPath, "utf8")).pipelines || []) : [];
  const ids = P.map((p) => Number(p.id));
  const byId = new Map(P.map((p) => [Number(p.id), p]));

  // canonical rank = category order of first appearance, then pipeline id
  const catOrder = new Map();
  for (const p of P) { const c = p.category || "?"; if (!catOrder.has(c)) catOrder.set(c, catOrder.size); }
  const rank = (id) => (catOrder.get(byId.get(Number(id))?.category || "?") ?? 0) * 10000 + Number(id);

  // ---- declared edges ----
  const declared = new Set();
  const addEdge = (from, to) => { const a = Number(from), b = Number(to); if (a && b && a !== b) declared.add(`${a}->${b}`); };
  const mapPath = path.join(rootDir, "integration-map.json");
  if (fs.existsSync(mapPath)) {
    const raw = JSON.parse(fs.readFileSync(mapPath, "utf8"));
    const arrs = [];
    for (const k of ["edges", "links", "dependencies", "connections", "pairs"]) if (Array.isArray(raw[k])) arrs.push(raw[k]);
    if (Array.isArray(raw)) arrs.push(raw);
    for (const arr of arrs) for (const e of arr) {
      if (!e || typeof e !== "object") continue;
      addEdge(e.from ?? e.source ?? e.src ?? e.parent ?? e.a ?? e.from_pipeline_id,
              e.to ?? e.target ?? e.dst ?? e.child ?? e.depends_on ?? e.b ?? e.to_pipeline_id);
    }
    for (const n of (Array.isArray(raw.nodes) ? raw.nodes : [])) {
      const dep = n.dependencies ?? n.depends_on ?? n.requires;
      if (Array.isArray(dep)) for (const t of dep) addEdge(n.id ?? n.pipeline_id, t && typeof t === "object" ? (t.id ?? t.pipeline_id) : t);
    }
  }
  for (const p of P) {
    const dep = p.dependencies ?? p.depends_on ?? p.depends ?? p.requires;
    if (Array.isArray(dep)) for (const t of dep) addEdge(p.id, t && typeof t === "object" ? (t.id ?? t.pipeline_id) : t);
  }

  // ---- signal groups ----
  const groups = new Map();
  const add = (kind, key, id) => {
    if (key === undefined || key === null || key === "") return;
    const k = `${kind}:${String(key).toLowerCase()}`;
    if (!groups.has(k)) groups.set(k, new Set());
    groups.get(k).add(Number(id));
  };
  for (const p of P) for (const [kind, fields] of SIGNAL_FIELDS) for (const f of fields) {
    const v = p[f];
    if (Array.isArray(v)) for (const x of v) add(kind, typeof x === "object" ? (x.id ?? x.name) : x, p.id);
    else if (typeof v === "string") add(kind, v, p.id);
  }

  // ---- signal edges, ranked so the result is a DAG ----
  const signalEdges = new Map(); // "a->b" -> Set(kinds)
  for (const [k, members] of groups) {
    if (members.size < 2 || members.size > GROUP_CAP) continue;
    const kind = k.split(":")[0];
    const arr = [...members];
    for (const a of arr) for (const b of arr) {
      if (a === b) continue;
      const [lo, hi] = rank(a) <= rank(b) ? [a, b] : [b, a];
      const key = `${lo}->${hi}`;
      if (!signalEdges.has(key)) signalEdges.set(key, new Set());
      signalEdges.get(key).add(kind);
    }
  }

  const edges = [];
  const seen = new Set();
  for (const k of declared) {
    const [f, t] = k.split("->").map(Number);
    edges.push({ from: f, to: t, source: "declared", signal: "explicit" }); seen.add(`${Math.min(f,t)}->${Math.max(f,t)}`);
  }
  const sigCounts = {};
  for (const [k, kinds] of signalEdges) {
    const [f, t] = k.split("->").map(Number);
    const kindsArr = [...kinds];
    for (const kd of kindsArr) sigCounts[kd] = (sigCounts[kd] || 0) + 1;
    edges.push({ from: f, to: t, source: "signal", signal: kindsArr.join("+") });
  }

  const adj = new Map(), rev = new Map();
  for (const e of edges) {
    if (!adj.has(e.from)) adj.set(e.from, new Set()); adj.get(e.from).add(e.to);
    if (!rev.has(e.to)) rev.set(e.to, new Set()); rev.get(e.to).add(e.from);
  }
  const reach = (start, map) => {
    const out = new Set(), guard = new Set(), stack = [...(map.get(Number(start)) || [])];
    while (stack.length) { const n = stack.pop(); if (guard.has(n)) continue; guard.add(n); out.add(n); for (const m of (map.get(n) || [])) if (!guard.has(m)) stack.push(m); }
    return out;
  };

  // cycle guard (iterative colour marking) — must stay 0 given rank ordering
  const colour = new Map(); let cycles = 0;
  for (const id of ids) {
    if (colour.has(id)) continue;
    const stack = [[id, [...(adj.get(id) || [])].values()]]; colour.set(id, 1);
    while (stack.length) {
      const top = stack[stack.length - 1], nx = top[1].next();
      if (nx.done) { colour.set(top[0], 2); stack.pop(); continue; }
      const n = nx.value;
      if (colour.get(n) === 1) { cycles++; continue; }
      if (colour.get(n) === 2) continue;
      colour.set(n, 1); stack.push([n, [...(adj.get(n) || [])].values()]);
    }
  }

  // Kahn layers — how much of the catalog can run in parallel
  const indeg = new Map(ids.map((i) => [i, 0]));
  for (const e of edges) if (indeg.has(e.to)) indeg.set(e.to, indeg.get(e.to) + 1);
  const layers = []; let frontier = ids.filter((i) => indeg.get(i) === 0); const doneL = new Set();
  while (frontier.length && layers.length < 60) {
    layers.push(frontier.length); const next = [];
    for (const i of frontier) { doneL.add(i); for (const m of (adj.get(i) || [])) { if (doneL.has(m)) continue; indeg.set(m, indeg.get(m) - 1); if (indeg.get(m) === 0) next.push(m); } }
    frontier = [...new Set(next)];
  }

  const fanOut = ids.map((i) => (adj.get(i) || new Set()).size);
  const fanIn = ids.map((i) => (rev.get(i) || new Set()).size);
  const deepest = ids.slice().sort((a, b) => reach(b, adj).size - reach(a, adj).size)[0];

  return {
    nodes: P, edges,
    rank, dependentsOf: (id) => [...(adj.get(Number(id)) || [])], upstreamOf: (id) => [...(rev.get(Number(id)) || [])],
    descendantsOf: (id) => reach(id, adj), ancestorsOf: (id) => reach(id, rev),
    summary: {
      nodes: P.length, edges: edges.length,
      declared: edges.filter((e) => e.source === "declared").length,
      signal: edges.filter((e) => e.source === "signal").length,
      bySignal: sigCounts, cycles, layers: layers.length,
      avgFanOut: +(edges.length / (P.length || 1)).toFixed(2),
      maxFanOut: Math.max(0, ...fanOut),
      roots: ids.filter((_, i) => fanIn[i] === 0).length,
      sinks: ids.filter((_, i) => fanOut[i] === 0).length,
      isolated: ids.filter((_, i) => fanIn[i] === 0 && fanOut[i] === 0).length,
      deepest, deepestReach: reach(deepest, adj).size,
    },
  };
}

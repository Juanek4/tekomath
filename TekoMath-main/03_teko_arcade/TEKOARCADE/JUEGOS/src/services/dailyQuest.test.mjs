import test from 'node:test'
import assert from 'node:assert/strict'
import { dailySineQuest, paraguayDay } from './dailyQuest.ts'
const date=new Date('2026-10-29T15:00:00Z')
const progress=()=>({gameHistory:[],claimedMissionRewards:[]})
test('daily mission starts empty and counts only todays sine correct answers',()=>{
 const p=progress();assert.equal(dailySineQuest(p,date).current,0)
 p.gameHistory=[{sessionId:'1',topicId:'cos',timestamp:date.toISOString(),correctAnswers:8},{sessionId:'2',topicId:'sin',timestamp:'2026-10-28T15:00:00Z',correctAnswers:9},{sessionId:'3',topicId:'sin',timestamp:date.toISOString(),correctAnswers:2}]
 assert.equal(dailySineQuest(p,date).current,2);assert.equal(dailySineQuest(p,date).completed,false)
 p.gameHistory.push({...p.gameHistory[2]});assert.equal(dailySineQuest(p,date).current,2)
 p.gameHistory.push({sessionId:'4',topicId:'sin',timestamp:date.toISOString(),correctAnswers:1});assert.equal(dailySineQuest(p,date).completed,true)
})
test('reward identity changes by Paraguay date and respects claimed rewards',()=>{
 const p=progress(),first=dailySineQuest(p,date);p.claimedMissionRewards=[first.id];assert.equal(dailySineQuest(p,date).claimed,true)
 assert.notEqual(dailySineQuest(p,new Date('2026-10-30T15:00:00Z')).id,first.id)
 assert.equal(paraguayDay(new Date('2026-10-30T01:00:00Z')),'2026-10-29')
})

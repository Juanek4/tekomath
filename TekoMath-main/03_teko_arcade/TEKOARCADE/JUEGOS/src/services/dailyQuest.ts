import type { UserProgress } from '../types'
export function paraguayDay(date: Date): string {
  const parts=new Intl.DateTimeFormat('en-CA',{timeZone:'America/Asuncion',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(date)
  return ['year','month','day'].map(key=>parts.find(p=>p.type===key)?.value).join('-')
}
export function dailySineQuest(progress: UserProgress, now=new Date()) {
  const day=paraguayDay(now),id='daily-sin-'+day,seen=new Set<string>();let solved=0
  for(const game of progress.gameHistory){
    const date=new Date(game.timestamp)
    if(game.topicId!=='sin'||!Number.isFinite(date.getTime())||paraguayDay(date)!==day||seen.has(game.sessionId))continue
    seen.add(game.sessionId)
    solved+=Number.isFinite(game.correctAnswers)?Math.max(0,Math.floor(game.correctAnswers!)):0
  }
  return {id,current:Math.min(3,solved),target:3,reward:30,completed:solved>=3,claimed:progress.claimedMissionRewards.includes(id)}
}

import { useState, useEffect, type SetStateAction, type Dispatch } from 'react'
export function usePersistentGameState<T>(sessionId:string, field:string, initial:T|(()=>T)): [T,Dispatch<SetStateAction<T>>] {
 const account=(window as Window & {__TEKO_ARCADE_STORAGE_KEY__?:string}).__TEKO_ARCADE_STORAGE_KEY__
 const key=account ? `${account}:game:${sessionId}:${field}` : null
 const [value,setValue]=useState<T>(()=>{
  if(key){try{const raw=sessionStorage.getItem(key);if(raw!==null)return JSON.parse(raw) as T}catch{/* Ignore corrupt tab cache. */}}
  return typeof initial==='function'?(initial as ()=>T)():initial
 })
 useEffect(()=>{if(key){try{sessionStorage.setItem(key,JSON.stringify(value))}catch{/* Game remains usable when tab storage is full. */}}},[key,value])
 return [value,setValue]
}

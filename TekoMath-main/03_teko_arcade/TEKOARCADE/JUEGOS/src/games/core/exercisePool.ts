import type { ExerciseDefinition } from '../../types'
import type { GameExercise } from './engine.types'
export function exercisePool(selected:GameExercise,bank:ExerciseDefinition[]):ExerciseDefinition[]{
 if(bank.some(exercise=>exercise.id===selected.id))return bank
 return [{id:selected.id,topic:selected.topicId,statement:selected.prompt,difficulty:selected.difficulty,source:selected.source,knownValues:selected.knownValues,unknown:selected.unknown,answer:String(selected.expectedAnswer),hints:selected.hints},...bank]
}

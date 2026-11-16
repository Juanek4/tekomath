<?php
/** Compatibility only. No remote uploads or fabricated image recognition. */
class ImageService {
 public static function processExerciseImage(array $file): array {
  return ['success'=>false,'error'=>'Usá el lector compartido de imágenes del navegador.'];
 }
}

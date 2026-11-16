<?php
/** Public registration never grants staff privileges. */
function publicRegistrationRole(mixed $requestedRole): string {
    return 'estudiante';
}
function validRegistrationToken(mixed $submitted, mixed $expected): bool {
    return is_string($submitted) && is_string($expected) && strlen($expected) === 64 && hash_equals($expected, $submitted);
}

$ErrorActionPreference = 'Stop'
$xamppRoot = Split-Path (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent) -Parent
$privateRoot = Join-Path $xamppRoot 'teko-private'
$executable = Join-Path $xamppRoot 'mysql/bin/mysqld.exe'
if (-not (Test-Path -LiteralPath $executable)) {
    throw 'No se encontró el ejecutable de MariaDB en la instalación esperada.'
}
$running = Get-CimInstance Win32_Process -Filter "name='mysqld.exe'"
if ($running) {
    throw 'MariaDB ya está ejecutándose. Revisá la instancia existente antes de iniciar otra.'
}
New-Item -ItemType Directory -Force -Path $privateRoot | Out-Null
$inputFile = Join-Path $privateRoot 'mysql-stdin.txt'
Set-Content -LiteralPath $inputFile -Value ''
# Explicit handles prevent detached Windows startup from reusing console handles for database files.
Start-Process -FilePath $executable -ArgumentList "--defaults-file=$xamppRoot/mysql/bin/my.ini", '--standalone', '--console' -WorkingDirectory $xamppRoot -WindowStyle Hidden -RedirectStandardInput $inputFile -RedirectStandardOutput (Join-Path $privateRoot 'mysql-stdout.log') -RedirectStandardError (Join-Path $privateRoot 'mysql-stderr.log')

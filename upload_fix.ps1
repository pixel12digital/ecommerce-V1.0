# Script para fazer upload do ProductController corrigido para produção
# Execute este script do PowerShell na sua máquina local (não no SSH)

$localFile = "c:\xampp\htdocs\pontodogolfe\src\Http\Controllers\Admin\ProductController.php"
$remoteUser = "u426126796"
$remoteHost = "147.93.38.248"
$remotePort = "65002"
$remotePath = "~/domains/pontodogolfeoutlet.com.br/public_html/src/Http/Controllers/Admin/"

Write-Host "Fazendo upload do ProductController.php para produção..." -ForegroundColor Cyan

# Comando SCP
scp -P $remotePort $localFile "${remoteUser}@${remoteHost}:${remotePath}"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`nUpload concluído com sucesso!" -ForegroundColor Green
    Write-Host "`nAgora teste o salvamento das variações em:" -ForegroundColor Yellow
    Write-Host "https://pontodogolfeoutlet.com.br/admin/produtos/958?q=teste&direction=asc" -ForegroundColor White
} else {
    Write-Host "`nErro no upload. Verifique as credenciais e tente novamente." -ForegroundColor Red
}

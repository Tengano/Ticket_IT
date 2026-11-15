$ErrorActionPreference = 'SilentlyContinue'
Write-Output '=== laragon process ==='
Get-Process laragon | ForEach-Object {
    Write-Output ("PID={0} Responding={1} MainWindowHandle={2} Title='{3}' Start={4}" -f $_.Id, $_.Responding, $_.MainWindowHandle, $_.MainWindowTitle, $_.StartTime)
}

Write-Output '=== windows with titles ==='
Get-Process | Where-Object { $_.MainWindowTitle } | ForEach-Object {
    Write-Output ("{0} PID={1} '{2}'" -f $_.ProcessName, $_.Id, $_.MainWindowTitle)
}

Write-Output '=== apache/httpd processes ==='
Get-Process httpd | ForEach-Object { Write-Output ("httpd PID={0} Start={1}" -f $_.Id, $_.StartTime) }

param(
    [Parameter(Mandatory=$true)]
    [string]$ImagePath
)

try {
    $fullPath = (Resolve-Path $ImagePath).Path
    if (-not (Test-Path $fullPath)) {
        Write-Error "File not found: $ImagePath"
        exit 1
    }

    $sysRuntime = [System.IO.Path]::Combine([System.Runtime.InteropServices.RuntimeEnvironment]::GetRuntimeDirectory(), "System.Runtime.WindowsRuntime.dll")
    if (Test-Path $sysRuntime) {
        Add-Type -Path $sysRuntime
    }

    [Windows.Media.Ocr.OcrEngine, Windows.Media, ContentType=WindowsRuntime] | Out-Null
    [Windows.Graphics.Imaging.BitmapDecoder, Windows.Graphics, ContentType=WindowsRuntime] | Out-Null
    [Windows.Storage.StorageFile, Windows.Storage, ContentType=WindowsRuntime] | Out-Null

    $asTaskGenericMethod = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
        $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and ($_.GetParameters().Count -eq 1)
    } | Select-Object -First 1

    function Await-Async($asyncOp, $returnType) {
        $method = $asTaskGenericMethod.MakeGenericMethod($returnType)
        $task = $method.Invoke($null, @($asyncOp))
        $task.Wait()
        return $task.Result
    }

    $file = Await-Async ([Windows.Storage.StorageFile]::GetFileFromPathAsync($fullPath)) ([Windows.Storage.StorageFile])
    $stream = Await-Async ($file.OpenAsync([Windows.Storage.FileAccessMode]::Read)) ([Windows.Storage.Streams.IRandomAccessStream])
    $decoder = Await-Async ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream)) ([Windows.Graphics.Imaging.BitmapDecoder])
    $bitmap = Await-Async ($decoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
    
    $engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
    if (-not $engine) {
        $engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromLanguage([Windows.Globalization.Language]::new("en-US"))
    }
    
    $res = Await-Async ($engine.RecognizeAsync($bitmap)) ([Windows.Media.Ocr.OcrResult])
    Write-Output $res.Text
} catch {
    Write-Error $_
    exit 1
}


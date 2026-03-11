param(
    [Parameter(Mandatory = $true)]
    [string]$PrinterName,

    [Parameter(Mandatory = $true)]
    [string]$FilePath
)

if (-not (Test-Path $FilePath)) {
    Write-Error "No existe el archivo a imprimir: $FilePath"
    exit 1
}

$source = @"
using System;
using System.Runtime.InteropServices;

public class RawPrinterHelper
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOCINFO
    {
        [MarshalAs(UnmanagedType.LPWStr)]
        public string pDocName;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string pOutputFile;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    public static extern bool OpenPrinter(string pPrinterName, out IntPtr phPrinter, IntPtr pDefault);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true, CharSet = CharSet.Unicode)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In] DOCINFO di);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);

    public static void SendBytesToPrinter(string printerName, byte[] bytes)
    {
        IntPtr printerHandle;

        if (!OpenPrinter(printerName, out printerHandle, IntPtr.Zero))
        {
            throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
        }

        try
        {
            DOCINFO docInfo = new DOCINFO();
            docInfo.pDocName = "Thermal Ticket";
            docInfo.pDataType = "RAW";

            if (!StartDocPrinter(printerHandle, 1, docInfo))
            {
                throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
            }

            try
            {
                if (!StartPagePrinter(printerHandle))
                {
                    throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
                }

                IntPtr unmanagedBytes = Marshal.AllocCoTaskMem(bytes.Length);

                try
                {
                    Marshal.Copy(bytes, 0, unmanagedBytes, bytes.Length);
                    int written;

                    if (!WritePrinter(printerHandle, unmanagedBytes, bytes.Length, out written) || written != bytes.Length)
                    {
                        throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
                    }
                }
                finally
                {
                    Marshal.FreeCoTaskMem(unmanagedBytes);
                }

                if (!EndPagePrinter(printerHandle))
                {
                    throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
                }
            }
            finally
            {
                if (!EndDocPrinter(printerHandle))
                {
                    throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
                }
            }
        }
        finally
        {
            ClosePrinter(printerHandle);
        }
    }
}
"@

Add-Type -TypeDefinition $source -Language CSharp

$bytes = [System.IO.File]::ReadAllBytes($FilePath)
[RawPrinterHelper]::SendBytesToPrinter($PrinterName, $bytes)

<?php

namespace App\Services;

trait BaseMailService
{
    public function sendAttachmentMail($toEmail, $subject, $attachment, $cc = "")
    {
        /* Attachment File */
        $fileName = $attachment["file_name"];
        $path = $attachment["path"];

        // Read the file content
        $file = $path.'/'.$fileName;
        $fileSize = filesize($file);
        $handle = fopen($file, "r");
        $content = fread($handle, $fileSize);
        fclose($handle);
        $content = chunk_split(base64_encode($content));

        /* Set the email header */
        // Generate a boundary
        $boundary = md5(uniqid(time()));

        // Email header
        $header = "From: Admin<admin@shop.eservciesgroup.com>".PHP_EOL;
        if ($cc) {
            $header .= "Cc: ". $cc .PHP_EOL;
        }
        $header .= "MIME-Version: 1.0".PHP_EOL;

        // Multipart wraps the Email Content and Attachment
        $header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"".PHP_EOL;
        $header .= "This is a multi-part message in MIME format.".PHP_EOL;
        $header .= "--".$boundary.PHP_EOL;

        // Email content
        // Content-type can be text/plain or text/html
        $message = "Please check the attachment Report!".PHP_EOL;
        $message .= "Thanks".PHP_EOL.PHP_EOL;
        $message .= "--".$boundary.PHP_EOL;

        // Attachment
        // Edit content type for different file extensions
        $message .= "Content-Type: application/xml; name=\"".$fileName."\"".PHP_EOL;
        $message .= "Content-Transfer-Encoding: base64".PHP_EOL;
        $message .= "Content-Disposition: attachment; filename=\"".$fileName."\"".PHP_EOL.PHP_EOL;
        $message .= $content.PHP_EOL;
        $message .= "--".$boundary."--";
        mail("{$toEmail}, jimmy.gao@eservicesgroup.com", $subject, $message, $header);
    }

    public function createExcelFile($fileName, $orderPath, $cellData)
    {
        $excelFile = \Excel::create($fileName, function ($excel) use ($cellData) {
            $excel->sheet('sheet1', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->store("xlsx",$orderPath);
        if($excelFile){
            return true;
        }
    }
}
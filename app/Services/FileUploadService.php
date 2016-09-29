<?php

namespace App\Services;

use Websafe\Blueimp\JqueryFileUploadHandler;

class FileUploadService extends JqueryFileUploadHandler
{
    protected $options;

    public function __construct($options = null, $initialize = true, $error_messages = null)
    {
        parent::__construct($options, $initialize, $error_messages);
    }

    protected function generate_unique_filename($filename = "")
    {

        if (isset($this->options['file_rename']) && $filename != "") {
            $extension = pathinfo($filename , PATHINFO_EXTENSION);

            if ( $extension != "" ) {
                $extension = "." . $extension;
            }

            return $this->options['file_rename'] . $extension;
        }

        return $filename;
    }

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null)
    {
        $file = new \stdClass();

        //MYEDIT to generate unique filename
        $file->name = $this->generate_unique_filename($name);
        $file->extension = pathinfo($name , PATHINFO_EXTENSION);
        $file->size = $this->fix_integer_overflow((int)$size);
        $file->type = $type;
        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handle_form_data($file, $index);
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) {
            mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $file_path = $this->get_upload_path($file->name);
            $append_file = $content_range && is_file($file_path) &&
            $file->size > $this->get_file_size($file_path);
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {

                move_uploaded_file($uploaded_file, $file_path);
            }
        } else {
            // Non-multipart uploads (PUT method support)
            file_put_contents(
                $file_path,
                fopen('php://input', 'r'),
                $append_file ? FILE_APPEND : 0
            );
        }
        $file_size = $this->get_file_size($file_path, $append_file);
        if ($file_size === $file->size) {
            $file->url = $this->get_download_url($file->name);
            if ($this->is_valid_image_file($file_path)) {
                $this->handle_image_file($file_path, $file);
            }
        } else {
            $file->size = $file_size;
            if (!$content_range && $this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = $this->get_error_message('abort');
            }
        }
        $this->set_additional_file_properties($file);
    }

    return $file;
  }

}

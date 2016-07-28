<?php

namespace App\Services;

class ApiBaseService extends PlatformOrderService
{
	private $request;
	private $schedule;

	public function __construct(\Request $request)
	{
		$this->request= $request;
	}

	public function saveDataToFile($data,$fileName)
	{
		$filePath=storage_path()."/app/ApiPlatform/".$this->getPlatformId()."/". $fileName ."/" . date("Y");
		if (!file_exists($filePath)) { mkdir($filePath, 0755, true);}
		$file=$filePath. "/" .date("Y-m-d-H-i").".txt";
		//write json data into data.json file
		if(file_put_contents($file, $data)) {
		    //echo 'Data successfully saved';
		   return $data;
		}
		return false;
	}

	public function getSchedule()
	{
		return $this->schedule;
	}

	public function setSchedule($value)
	{
		$this->schedule=$value;
	}

}
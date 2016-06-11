<?php namespace App\Http\Controllers;

class FannController extends Controller {

	public function train()
	{
    	//$ann = fann_create(array(256, 128, 3), 1.0, 0.7);
    	$ann = fann_create_standard(3, 3, 256, 3);
		//$ann = fann_create_standard_array(3,array(2,4,2));

    	//fann_train($ann,
    	fann_train($ann,
    		array(
    			array(
            		$this->generate_frequencies(file_get_contents(storage_path('app/public') . "/en.txt")), // Inputs
            	//array(1, 0, 0) // Outputs
            	),
    			array(
            		$this->generate_frequencies(file_get_contents(storage_path('app/public') . "/fr.txt")), // Inputs
            	//	array(0, 1, 0) // Outputs
            	),
    			array(
            		$this->generate_frequencies(file_get_contents(storage_path('app/public') . "/pl.txt")), // Inputs
            	//array(0, 0, 1) // Outputs
            	),	
    		),
    		array(-1,0,1)
    	); 

    	fann_save($ann, storage_path('app/public') ."/classify.txt");   
    	fann_destroy($ann);    	
    	exit('Treinamento concluido.');
    }

    public function run()
    {
    	$ann = fann_create_from_file(storage_path('app/public') ."/classify.txt");   
    	$text = file_get_contents(storage_path('app/public') ."/file_1.txt");
    	$output = fann_run($ann, array($this->generate_frequencies($text), '', ''));

    	print_r($output);

    }	

    function generate_frequencies($text){
        $text = preg_replace('/[^\p{L}\p{N}\s]/', '', strtolower($text));
        $total = strlen($text);
        $data = count_chars($text);

        array_walk($data, function (&$item, $key, $total){
            $item = round($item/$total, 3);
        }, $total);

        return array_values($data);
    }
}

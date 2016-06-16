<?php 
namespace App\Http\Controllers;
use File;

class FannController extends Controller {

	public function train() {
        $num_input              = 26;
        $num_output             = 4;
        $num_layers             = 3;
        $num_neurons_hidden     = 13;
        $desired_error          = 0.00001;
        $max_epochs             = 6000;
        $epochs_between_reports = 100;

        $ann = fann_create_standard($num_layers, $num_input, $num_neurons_hidden, $num_output);
        
        if ($ann) {

            $filename = $this->generate_data_file($num_input, $num_output);

            fann_set_training_algorithm($ann, FANN_TRAIN_INCREMENTAL);

            $train_data = fann_read_train_from_file( $filename );        
            fann_train_on_data( $ann, $train_data, $max_epochs, $epochs_between_reports, $desired_error );

            if (fann_save($ann, storage_path('app/public') ."/classify.data")) {
                exit('Treinamento concluido.');                    
            }            
        }

        fann_destroy($ann);
    }

    public function run($file_name) {
        $ann = fann_create_from_file(storage_path('app/public') ."/classify.data");   
        $text = file_get_contents(storage_path('app/public') . "/" . $file_name);
        $output = fann_run($ann, $this->generate_frequencies($text));
        $maxs = array_keys($output, max($output));        
        $this->display_language_name($maxs[0]);
    }	

    function display_language_name($index) {
        switch ($index) {
            case 0:
                echo "Inglês";
            break;
            case 1:
                echo "Francês";
            break;
            case 2:
                echo "Português";
            break;
            case 3:
                echo "Alemão";
            break;
        }                
    }

    function process_file($file_name) {

        $text = file_get_contents($file_name);
        $data = $this->generate_frequencies($text);
        return $data;
    }

    function generate_frequencies($text) {

        $text = preg_replace("([^a-z])","", strtolower($text));  
        $text = preg_replace('/\s+/', '', strtolower($text));        
        $total = strlen($text);
        $data = count_chars($text,1);   
        $num  = count($data);
        //completa 26 itens do array se necessário
        for ($i=$num; $i < 26; $i++) { 
            $data[] = 0;
        }     
        /*
        foreach (count_chars($text, 1) as $i => $val) {
            echo "There were $val instance(s) of \"" , chr($i) , "\" in the string.\n<br>";
        }  */          
        array_walk($data, function (&$item, $key, $total) {
            $item = round($item/$total, 3);            
        }, $total);

        return array_values($data);
    }

    function generate_data_file($inputs, $outputs) { 

        $filename = $directory = storage_path('app/public/train.data'); 
        $count    = 0;
        $header   = null;
        $data     = null;
        $folders  = array(
            'en' => array(1,0,0,0), 
            'fr' => array(0,1,0,0), 
            'pt' => array(0,0,1,0),
            'ge' => array(0,0,0,1),
        );
        
        foreach ($folders as $key => $value) {
       
            $directory = storage_path('app/public/' . $key); 
            $files = File::allFiles($directory);

            foreach ($files as $file) {
                $base = $this->process_file($file);
                $data .= implode(" ", $base) . "\n"; 
                $data .= implode(" ", $value) . "\n"; 
                $count++;
            }        
        }

        $header  = $count . ' ' . $inputs . ' '. $outputs . "\n";    
        $content = $header . $data;
        
        $bytes_written = File::put($filename, $content);
        if ($bytes_written === false) {
            die("Erro ao gerar arquivo de dados.");
        }        

        return $filename;
    }
}

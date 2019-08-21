<?php
// Datasource plugin for influxdb, target is defined as:
// influxdb:host:database:host:seriesin:seriesout

class WeatherMapDataSource_influxdb extends WeatherMapDataSource {

        private $regex_pattern = "/^influxdb:(.*):(.*):(.*):(.*):(.*):(.*)$/";

        function Init(&$map)
        {
                return(TRUE);
        }


        function Recognise($targetstring)
        {
                if(preg_match($this->regex_pattern,$targetstring,$matches))
                {
                        return TRUE;
                }
                else
                {
                        return FALSE;
                }
        }

        function ReadData($targetstring, &$map, &$item)
        {
                $data[IN] = NULL;
                $data[OUT] = NULL;
                $data_time = time();

                if(preg_match($this->regex_pattern,$targetstring,$matches))
                {
                        $host = $matches[1];
                        $database = $matches[2];
                        $device = $matches[3];
                        $instance = $matches[4];
                        $seriesin = $matches[5];
                        $seriesout = $matches[6];
                        $instance = str_replace("--", " ", $instance);
			$buffer = "";
                        $query = urlencode("SELECT non_negative_derivative(mean(\"value\"), 1s) *8 FROM \"$seriesin\" WHERE (\"host\" = '$device' AND \"type_instance\" = '$instance') AND time > now() - 30s GROUP BY time(30s)");
                        $file = "http://$host:8086/query?db=$database&q=$query";
			                       $fp = fopen($file,"r");
                        while (!feof($fp)) {
                                $buffer .= fgets($fp,4096);
                        }
                        fclose($fp);
                        $decoded = json_decode($buffer);
                        $dat=($decoded->results[0]->series[0]->values[0]);
                        print_r($dat);
                        $data[IN] = round($dat[1]);
                        print_r($data[IN]);

                        $buffer = "";
                        $query = urlencode("SELECT non_negative_derivative(mean(\"value\"), 1s) *8 FROM \"$seriesout\" WHERE (\"host\" = '$device' AND \"type_instance\" = '$instance') AND time > now() - 30s GROUP BY time(30s)");
                        $file = "http://$host:8086/query?db=$database&q=$query";
                        $fp = fopen($file,"r");
                        while (!feof($fp)) {
                                $buffer .= fgets($fp,4096);
                        }
                        fclose($fp);
                        $decoded = json_decode($buffer);
                        $dat=($decoded->results[0]->series[0]->values[0]);
                        $data[OUT] = round($dat[1]);
                }
                return( array($data[IN], $data[OUT], $data_time) );
        }
}
?>


<?php
//this code is released under creative common license read the end of the file for clarification.
    $server   = '<yourcloudserver>.atlassian.net';
    $fromDate = '';
    $toDate   = '';
    $project  = '';
    $assignee = '';
    $username = '';
    $password = '';
    $invoice = '';
    $totalTimeLogged = 0;
    //$username= $_POST['user'];
    //$password = $_POST['password'];
    $project = $_POST['projectCode'];
    $fromDate = $_POST['fromDate'];
    $toDate = $_POST['toDate'];
    $assignee = $_POST['assignee'];
    $invoice = $_POST['invoice'];
                    //clear the file from the previous data
                    $myfile = fopen("timesheet.txt", "w") or die("Unable to open file!");
                    $txt = ("Issue n.;Description;Employee;Time Lgged;Time Logged(sec);Log Date"."\r\n");
                    fwrite($myfile,$txt.PHP_EOL);
                    fclose($myfile);
    if ($project !="" || $assignee!= "" || $fromDate != "" || $toDate != ""){
        include("timesheet.html");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);


        # Give me up to 1000 search results with the Key, where
        # assignee = $assignee  AND  project = $project
        #  AND created < $toDate  AND  updated > $fromDate
        #  AND timespent > 0
        if ($project != "" && $fromDate == "" && $toDate == ""){
          curl_setopt($curl, CURLOPT_URL,
                    "https://$server/rest/api/2/search?startIndex=0&jql=project+%3D+$project+and+timespent+%3E+0&fields=key+0&fields=summary&maxResults=1000");
        }elseif ($project == "" && $fromDate != "" && $toDate != ""){
          curl_setopt($curl, CURLOPT_URL,
                                "https://$server/rest/api/2/search?startIndex=0&jql=created+%3C+$toDate+and+updated+%3E+$fromDate+and+timespent+%3E+0&fields=key+0&fields=summary&maxResults=1000");
        }elseif ($project != "" && $fromDate != "" && $toDate != ""){
             curl_setopt($curl, CURLOPT_URL,
                    "https://$server/rest/api/2/search?startIndex=0&jql=project+%3D+$project+and+created+%3C+$toDate+and+updated+%3E+$fromDate+and+timespent+%3E+0&fields=key+0&fields=summary&maxResults=1000");
        }
        //if ($project != "" && $fromDate == "" && $toDate == "") {
          //curl_setopt($curl, CURLOPT_URL,
            //        "https://$server/rest/api/2/search?startIndex=0&jql=project+%3D+$project+and+timespent+%3E+0&fields=key+0&fields=summary&maxResults=1000");
        //}

        $issues = json_decode(curl_exec($curl), true);
        //echo($invoice);
        //var_dump($issues);
        #echo json_encode($issues);
        //echo '<pre>'; var_dump($issues);
        echo("<div class=\"container\">"."<table class=\"table table-responsive table-striped\">"."<thead class=\"background-color:#229cd0;\">"."<tr>"."<th>Issue n.</th>"."<th>Descritpion</th>"."<th>Employee</th>"."<th>Time Logged</th>"."<th>Time Logged (sec)</th>"."<th>Log Date</th>"."</tr>"."</thead>");

        if ($assignee != "" && $fromDate != "" && $toDate != ""){
            //echo("filter on employee");
            foreach ($issues['issues'] as $issue) {
            $key = $issue['key'];
            $description = $issue['fields']['summary'];
            //retrive worklog
            # for each issue in result, give me the full worklog for that issue
            curl_setopt($curl, CURLOPT_URL,
                        "https://$server/rest/api/2/issue/$key/worklog");
            $worklog = json_decode(curl_exec($curl), true);
            //check base on the employee name
                foreach ($worklog['worklogs'] as $entry) {
               $name= ($entry['updateAuthor']['displayName']);
               $shortDate = substr($entry['started'], 0, 10);
                if ($shortDate >= $fromDate && $shortDate <= $toDate && $assignee == $name){
                      $user= ($entry['updateAuthor']['displayName']);
                      $timespent = ($entry['timeSpent']);
                      $time= ($entry['timeSpentSeconds']);
                      //this is good only for mockup to be done better in function
                      //check if it is timesheet or invoicing --------------------------------------------------------
                     if ($invoice == "Invoice" && $time <= 900){
                         $time = 900;
                         $hours1 = floor($time / 3600);
                         $minutes1 = floor(($time / 60) % 60);
                         $seconds1 = $time % 60;
                         $timespent = ($hours1."h:".$minutes1."m:".$seconds1."s");
                     }
                     //end of check----------------------------------------------------------------------------------
                      $date= substr($entry['started'], 0, 10);
                      echo("<td>$key</td>"."<td>$description</td>"."<td>$user</td>"."<td>$timespent</td>"."<td>$time</td>"."<td>$date</td>"."</tr>");
                      $totalTimeLogged = $totalTimeLogged + $time;
                    //Write on file
                     $myfile = fopen("timesheet.txt", "a") or die("Unable to open file!");
                     $txt = ("$key".";"."$description".";"."$user".";"."$timespent".";"."$time".";"."$date"."\r\n");
                     fwrite($myfile,$txt.PHP_EOL);
                     fclose($myfile);

                }
                }
            }

        }
        //time spent of project by date range
        if ($project != "" && $fromDate != "" && $toDate != ""){
            //echo("filter on employee");
            foreach ($issues['issues'] as $issue) {
            $key = $issue['key'];
            $description = $issue['fields']['summary'];
            //retrive worklog
            # for each issue in result, give me the full worklog for that issue
            curl_setopt($curl, CURLOPT_URL,
                        "https://$server/rest/api/2/issue/$key/worklog");
            $worklog = json_decode(curl_exec($curl), true);
            //check base on the employee name
                foreach ($worklog['worklogs'] as $entry) {
               $name= ($entry['updateAuthor']['displayName']);
               $shortDate = substr($entry['started'], 0, 10);
                if ($shortDate >= $fromDate && $shortDate <= $toDate){
                      $user= ($entry['updateAuthor']['displayName']);
                      $timespent = ($entry['timeSpent']);
                      $time= ($entry['timeSpentSeconds']);
                     //check if it is timesheet or invoicing --------------------------------------------------------
                    if ($invoice == "Invoice" && $time <= 900){
                        $time = 900;
                        $hours1 = floor($time / 3600);
                        $minutes1 = floor(($time / 60) % 60);
                        $seconds1 = $time % 60;
                        $timespent = ($hours1."h:".$minutes1."m:".$seconds1."s");
                    }
                    //end of check----------------------------------------------------------------------------------
                      $date= substr($entry['started'], 0, 10);
                      echo("<td>$key</td>"."<td>$description</td>"."<td>$user</td>"."<td>$timespent</td>"."<td>$time</td>"."<td>$date</td>"."</tr>");
                      $totalTimeLogged = $totalTimeLogged + $time;
                    //Write on file
                     $myfile = fopen("timesheet.txt", "a") or die("Unable to open file!");
                     $txt = ("$key".";"."$description".";"."$user".";"."$timespent".";"."$time".";"."$date"."\r\n");
                     fwrite($myfile,$txt.PHP_EOL);
                     fclose($myfile);

                }
                }
            }

        }



        //end
        //all issue without employee
        if ($assignee == "" && $project=="" && $fromDate != ""){
            //echo("no filter on employee");
            foreach ($issues['issues'] as $issue) {
            $key = $issue['key'];
            $description = $issue['fields']['summary'];
            //retrive worklog
            # for each issue in result, give me the full worklog for that issue
            curl_setopt($curl, CURLOPT_URL,
                        "https://$server/rest/api/2/issue/$key/worklog");
            $worklog = json_decode(curl_exec($curl), true);
            //check base on the employee name

                $name= ($entry['updateAuthor']['displayName']);
                foreach ($worklog['worklogs'] as $entry) {
                $shortDate = substr($entry['started'], 0, 10);
                if ($shortDate >= $fromDate && $shortDate <= $toDate) {
                    $user= ($entry['updateAuthor']['displayName']);
                    $timespent = ($entry['timeSpent']);
                    $time= ($entry['timeSpentSeconds']);
                    //check if it is timesheet or invoicing --------------------------------------------------------
                    if ($invoice == "Invoice" && $time <= 900){
                        $time = 900;
                        $hours1 = floor($time / 3600);
                        $minutes1 = floor(($time / 60) % 60);
                        $seconds1 = $time % 60;
                        $timespent = ($hours1."h:".$minutes1."m:".$seconds1."s");
                    }
                    //end of check----------------------------------------------------------------------------------
                    $date= substr($entry['started'], 0, 10);
                    echo ("<td>$key</td>"."<td>$description</td>"."<td>$user</td>"."<td>$timespent</td>"."<td>$time</td>"."<td>$date</td>"."</tr>");
                    $totalTimeLogged = $totalTimeLogged + $time;
                    //Write on file
                    $myfile = fopen("timesheet.txt", "a") or die("Unable to open file!");
                    $txt = ("$key".";"."$description".";"."$user".";"."$timespent".";"."$time".";"."$date"."\r\n");
                    fwrite($myfile,$txt.PHP_EOL);
                    fclose($myfile);
                    }
                }
            }

        }
        //total time spent on project
        if ($project != "" && $fromDate == "" && $toDate == "" && $assignee == ""){
            //echo("no filter on employee");
            foreach ($issues['issues'] as $issue) {
            $key = $issue['key'];
            $description = $issue['fields']['summary'];
            //retrive worklog
            # for each issue in result, give me the full worklog for that issue
            curl_setopt($curl, CURLOPT_URL,
                        "https://$server/rest/api/2/issue/$key/worklog");
            $worklog = json_decode(curl_exec($curl), true);
            //check base on the employee name

                $name= ($entry['updateAuthor']['displayName']);
                foreach ($worklog['worklogs'] as $entry) {
                    $user= ($entry['updateAuthor']['displayName']);
                    $timespent = ($entry['timeSpent']);
                    $time= ($entry['timeSpentSeconds']);
                    $date= substr($entry['started'], 0, 10);
                    echo ("<td>$key</td>"."<td>$description</td>"."<td>$user</td>"."<td>$timespent</td>"."<td>$time</td>"."<td>$date</td>"."</tr>");
                    $totalTimeLogged = $totalTimeLogged + $time;
                    //Write on file
                    $myfile = fopen("timesheet.txt", "a") or die("Unable to open file!");
                    $txt = ("$key".";"."$description".";"."$user".";"."$timespent".";"."$time".";"."$date"."\r\n");
                    fwrite($myfile,$txt.PHP_EOL);
                    fclose($myfile);
                    }
                }
            }
            //total time spent on project by selected useer
            if ($project != "" && $fromDate == "" && $toDate == "" & $assignee !=""){
                //echo("no filter on employee");
                foreach ($issues['issues'] as $issue) {
                $key = $issue['key'];
                $description = $issue['fields']['summary'];
                //retrive worklog
                # for each issue in result, give me the full worklog for that issue
                curl_setopt($curl, CURLOPT_URL,
                            "https://$server/rest/api/2/issue/$key/worklog");
                $worklog = json_decode(curl_exec($curl), true);
                //check base on the employee name

                    $name= ($entry['updateAuthor']['displayName']);
                    foreach ($worklog['worklogs'] as $entry) {
                        $name= ($entry['updateAuthor']['displayName']);
                        if ($assignee == $name){
                        $user= ($entry['updateAuthor']['displayName']);
                        $timespent = ($entry['timeSpent']);
                        $time= ($entry['timeSpentSeconds']);
                        $date= substr($entry['started'], 0, 10);
                        echo ("<td>$key</td>"."<td>$description</td>"."<td>$user</td>"."<td>$timespent</td>"."<td>$time</td>"."<td>$date</td>"."</tr>");
                        $totalTimeLogged = $totalTimeLogged + $time;
                        //Write on file
                        $file = $timestamp."timesheet.txt";
                        echo($file);
                        $myfile = fopen($file, "a") or die("Unable to open file!");
                        $txt = ("$key".";"."$description".";"."$user".";"."$timespent".";"."$time".";"."$date"."\r\n");
                        fwrite($myfile,$txt.PHP_EOL);
                        fclose($myfile);
                        }
                      }
                    }
                }
            echo("</table>");
            if ($totalTimeLogged > 0){
                $hours = floor($totalTimeLogged / 3600);
                $minutes = floor(($totalTimeLogged / 60) % 60);
                $seconds = $totalTimeLogged % 60;
                echo("<label class=\"mr-sm-2\" for=\"inlineFormInput\">Total Time Logged="."$hours"."h:"."$minutes"."m:"."$seconds"."s"."</label>");
            }

            echo("<br>");
            echo("<hr>");
            echo("<a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc/4.0/\"><img alt=\"Creative Commons License\" style=\"border-width:0\" src=\"https://i.creativecommons.org/l/by-nc/4.0/88x31.png\" /></a><br />This work is licensed under a <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc/4.0/\">Creative Commons Attribution-NonCommercial 4.0 International License</a>.");
            echo("</div>");
      }else{
        include("timesheet.html");
        echo("<div class=\"container\">");
        echo("<br>");
        echo("<hr>");
        echo("<a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc/4.0/\"><img alt=\"Creative Commons License\" style=\"border-width:0\" src=\"https://i.creativecommons.org/l/by-nc/4.0/88x31.png\" /></a><br />This work is licensed under a <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc/4.0/\">Creative Commons Attribution-NonCommercial 4.0 International License</a>.");
        echo("</div>");
      }
 ?>
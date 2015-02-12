<?php


/***
*
*
* This is a separated experiment, indipendent from salesman.php
*
*/

require 'lib/sim.php';

/**
* activities1 is films
* activities2 is restaurants
**/

define("STATE_START", 0);
define("STATE_ACT", 1);# map user -> action

define("ACTIVITY_CELL", 0);
define("ACTIVITY_START", 1);
define("ACTIVITY_END", 2);
define("ACTIVITY_DURATION", 3);
define("ACTIVITY_TYPE", 4);

define("MAXSEQUENCE", 2); # number of phases

$DZNINSTANCE = '../test/2_phases_simple_instance.dzn';

$USERSN = NULL;
$CELLSN = 0;
$MAXTIME = NULL;
$MINGROUPSIZE = NULL;
$MAXGROUPSIZE = NULL;
$GROUPSN = 0;
$MAXWAIT = NULL;

$activities = array();
$preferences = array();
$distances = array();

dzn_import($DZNINSTANCE);

function dzn_import($file_path){

  $USERSN = $GLOBALS['USERSN'];
  $MAXTIME = $GLOBALS['MAXTIME'];
  $MINGROUPSIZE = $GLOBALS['MINGROUPSIZE'];
  $MAXGROUPSIZE = $GLOBALS['MAXGROUPSIZE'];
  $MAXWAIT = $GLOBALS['MAXWAIT'];
  $activities = $GLOBALS['activities'];
  $preferences = $GLOBALS['preferences'];
  $distances =  $GLOBALS['distances'];
  $CELLSN =  $GLOBALS['CELLSN'];
  $GROUPSN =  $GLOBALS['GROUPSN'];

  for ($i=0;$i<MAXSEQUENCE; $i++){
   $activities[$i] = array();
 }

 $lines = file($file_path);
 foreach ($lines as $line_num => $line) {
  $splitted = split("=", $line);
  $arg = trim($splitted[0]);
  $content = trim($splitted[1]);
  $lss = str_replace(";", "", $content);
  $ls = split("\.", $lss);
  if ($arg == "user_ids") {
    $USERSN = intval($ls[2]);
  }else if ($arg == "activity1_ids") {
    $ACTIVITIES1N = intval($ls[2]);
  }else if ($arg == "activity2_ids") {
    $ACTIVITIES2N = intval($ls[2]);
  }else if ($arg == "cell_ids") {
    $CELLSN = intval($ls[2]);
  }else if ($arg == "group_ids") {
    $GROUPSN = intval($ls[2]);
  }else if ($arg == "type_ids") {
    $iAXTYPE = intval($ls[2]);
  }else if ($arg == "time_slot_ids") {
    $MAXTIME = intval($ls[2]);
  }else if ($arg == "min_group_size") {
    $MINGROUPSIZE = intval($ls[0]);
  }else if ($arg == "max_group_size") {
    $MAXGROUPSIZE = intval($ls[0]);
  }else if ($arg == "max_wait") {
    $MAXWAIT = intval($ls[0]);
  }else if ($arg == "preferences") {
    $csplitted = split("\|", $content);
    for ($i=1; $i < count($csplitted) -1; $i++) { 
      $pieces = split(",", $csplitted[$i]);
      $tmparr = array();
      for ($j=0; $j < count($pieces); $j++) { 
        $tmparr[] = trim($pieces[$j]);
      }
      $preferences[] = $tmparr;
    }
  }else if ($arg == "activities1") {
    $csplitted = split("\|", $content);
    for ($i=1; $i < count($csplitted) -1; $i++) { 
      $pieces = split(",", $csplitted[$i]);
      $tmparr = array();
      for ($j=0; $j < count($pieces); $j++) { 
        $tmparr[] = trim($pieces[$j]);
      }
      $activities[0][] = $tmparr;
    }
  }else if ($arg == "activities2") {
    $csplitted = split("\|", $content);
    for ($i=1; $i < count($csplitted) -1; $i++) { 
      $pieces = split(",", $csplitted[$i]);
      $tmparr = array();
      for ($j=0; $j < count($pieces); $j++) { 
        $tmparr[] = trim($pieces[$j]);
      }
      $activities[1][] = $tmparr;
    }
  }else if ($arg == "distances") {
    $csplitted = split("\|", $content);
    for ($i=1; $i < count($csplitted) -1; $i++) { 
      $pieces = split(",", $csplitted[$i]);
      $tmparr = array();
      for ($j=0; $j < count($pieces); $j++) { 
        if (trim($pieces[$j]) != "") {
         $tmparr[] = trim($pieces[$j]);
       }
     }
     $distances[] = $tmparr;
   }
 }
}
$GLOBALS['USERSN'] = $USERSN;
$GLOBALS['MAXTIME'] =$MAXTIME;
$GLOBALS['MINGROUPSIZE'] = $MINGROUPSIZE;
$GLOBALS['MAXGROUPSIZE'] = $MAXGROUPSIZE;
$GLOBALS['MAXWAIT'] = $MAXWAIT;
$GLOBALS['activities'] = $activities;
$GLOBALS['preferences'] = $preferences;
$GLOBALS['distances'] = $distances;
$GLOBALS['CELLSN'] = $CELLSN;
$GLOBALS['GROUPSN'] = $GROUPSN;

//debug
// echo "Debugging:\n";
// echo "USERSN:".$USERSN."\n";
// echo "MAXTIME:".$MAXTIME."\n";
// echo 'MINGROUPSIZE:'.$MINGROUPSIZE."\n";
// echo 'MAXGROUPSIZE:'.$MAXGROUPSIZE."\n";
// echo 'MAXWAIT:'.$MAXWAIT."\n";
// echo 'CELLSN:'.$CELLSN."\n";
// echo 'GROUPSN:'.$GROUPSN."\n";
// echo "activities[1][1]:\n";
// var_dump($activities[1][1]);
// echo "preferences[1][1]:\n";
// var_dump($preferences[1][1]);
// echo "distances[1][2]:\n";
// var_dump($distances[1][2]);


}


class GroupMeProblem extends Annealer {

  var $state = array();


/**
*
*$state = {
*  phase1(0): {
*           STATE_ACT(0): {
*               1:acti,
*               2:acti,
*               3:actj, 
*               ...
*           },
*           STATE_START(1): {
*               1:12:30,
*               2:12:30,
*               3:13:30, 
*               ...
*           },
*      },
* phase2(1): ...
*}
*
*
**/

  public function __construct($init_state){
    $this->state = $init_state;
    srand(32423423);
  }

  function delete_activity($user,$phase){
   unset($this->state[$phase][STATE_ACT][$user]); 
   unset($this->state[$phase][STATE_START][$user]);
 }

  //check if exists activity overlap for a user
  function overlap($user,$activity,$start,$phase){
    $activities = $GLOBALS['activities'];
    $distances = $GLOBALS['distances'];
    for ($i=$phase+1; $i < MAXSEQUENCE; $i++) { 
      
      //var_dump($this->state);
      if ($user == 7) {
        echo "\n DEBUGGG user:$user,act:$activity,start:$start,ph:$phase \n";
        echo "\n calculating time for next act start:".$start.";".$activities[$phase][$activity][ACTIVITY_DURATION].";".$distances[$activities[$phase][$activity][ACTIVITY_CELL]][$activities[$i][$this->state[$i][STATE_ACT][$user]][ACTIVITY_CELL]]."\n";
      }
      $timeForTheNextActivity = $start + $activities[$phase][$activity][ACTIVITY_DURATION] + $distances[$activities[$phase][$activity][ACTIVITY_CELL]][$activities[$i][$this->state[$i][STATE_ACT][$user]][ACTIVITY_CELL]];
      
      if ($user == 7)
      echo "\n state act:".STATE_ACT." state start:".STATE_START." user:".$user." timeForTheNextActivity:".$timeForTheNextActivity."\n";
      //var_dump($this->state);
      if (array_key_exists($user, $this->state[$i][STATE_ACT])) {
          
          if ($user == 7)
          echo "\n time1(act:$activity): $timeForTheNextActivity phase next start time2(act:".$this->state[$i][STATE_ACT][$user]."): ".$this->state[$i][STATE_START][$user]."\n";
          
          if($timeForTheNextActivity > $this->state[$i][STATE_START][$user]) {
          
          if ($user == 7)
          echo "\nso rejected\n";

          return $i;
        }else{
          if ($user == 7)
          echo "NOT rejected \n";
        }
      }
    }
  
    //the start of last activity should be suitable for this one, considering also distance.
    for ($i=0; $i < $phase; $i++) { 
        $first = $activities[$i][$this->state[$i][STATE_ACT][$user]][ACTIVITY_CELL];
        $second = $activities[$phase][$activity][ACTIVITY_CELL];
        $timeForTheNextActivity = $this->state[$i][STATE_START][$user] + $activities[$i][$this->state[$i][STATE_ACT][$user]][ACTIVITY_DURATION] + $distances[$first][$second];
        
       // echo "\n phase 0 end ".$timeForTheNextActivity." "."\n";
        if (array_key_exists($user, $this->state[$i][STATE_ACT]) && $timeForTheNextActivity > $start) {
          // echo "\nso rejected\n";
          return $i;
        }
    }
    return NULL;  
  }


//Calculates the metric function.
 function energy(){
  $USERSN = $GLOBALS['USERSN'];
  $preferences = $GLOBALS['preferences'];
  $activities = $GLOBALS['activities'];

  $metric = 0;
  //for those users that have been assign an activity
  for ($i=0; $i < MAXSEQUENCE; $i++) { 
    foreach ($this->state[$i][STATE_ACT] as $j => $value) {
      $k = $activities[$i][$this->state[$i][STATE_ACT][$j]][ACTIVITY_TYPE];
      $metric -= $preferences[$j][$k];
    }

  }

  # -20 if a user is not assigned to an activity
  for ($i=0; $i < MAXSEQUENCE; $i++){ 
    for ($j=0; $j < $USERSN; $j++){
      if(!array_key_exists($j, $this->state[$i][STATE_ACT])){
        $metric += 20;
        // echo "plus \n";
      }
    }
  }

   
 // echo "energy $metric \n";
  return $metric;
}

  /*******************************************/
  /**  ---------    MOVE  ---------- **/
  /*******************************************/

function move(){
      #add an activity to the system
      #an activity should have a positive weight

  $USERSN = $GLOBALS['USERSN'];

  $MINGROUPSIZE = $GLOBALS['MINGROUPSIZE'];
  $MAXGROUPSIZE = $GLOBALS['MAXGROUPSIZE'];
  $preferences = $GLOBALS['preferences'];
  $activities = $GLOBALS['activities'];

  //move only one activity phase
  $phase = rand(0, MAXSEQUENCE-1);
  $gain = 0;
  $activity;

  /*******************************************/
  /**  randomly choose ACT and set of USERS **/
  /*******************************************/
  while ($gain == 0) {

    //randomly choose an activity from this phase
    $activity = rand(0,count($activities[$phase])-1);

    //choose set of users that will be influenced
    $j = rand($MINGROUPSIZE,$MAXGROUPSIZE);

    $users = array_rand(range(0,$USERSN-1), $j);

    foreach ($users as $keyi => $i) {
      $gain += $preferences[$i][$activities[$phase][$activity][ACTIVITY_TYPE]];
    }
  }
  
  //calculate activity starting time
  $intern = $activities[$phase][$activity][ACTIVITY_END] - $activities[$phase][$activity][ACTIVITY_DURATION];
  $starting_time = rand($activities[$phase][$activity][ACTIVITY_START],$intern);





  # set chosen activity for every user
  # possible deleting incompatible ones
  foreach ($users as $keyj => $i) {

      $this->state[$phase][STATE_ACT][$i] = $activity;
      $this->state[$phase][STATE_START][$i] = $starting_time;



      $phase_to_del = $this->overlap($i,$activity,$starting_time,$phase);
      //echo "phase_to_del! (i:$i,ph:$phase_to_del) \n";

      if ($i == 7) {
        echo "-+++++++++++++++before ++++++++++++++++\n";
        var_dump($this->state);
        checkGlobalOverlapByState($this->state);
        echo "\n phase_to_del is $phase_to_del \n";
      }


      while($phase_to_del !== NULL){
        if ($i == 7) {
           echo "going to delete delete_activity(i:$i,fase:$phase_to_del)\n";
        }
          $this->delete_activity($i,$phase_to_del);
        //  echo "deleting delete_activity($i,$phase_to_del) \n";
          $phase_to_del = $this->overlap($i,$activity,$starting_time,$phase);
         // echo "phase_to_del!!!! ($phase_to_del) \n";
      }


      if ($i == 7) {
        echo "-+++++++++++++++after ++++++++++++++++\n";
        var_dump($this->state);
        checkGlobalOverlapByState($this->state);
      }

  }

  

  //map user activities, checked
  $act_map = get_actMapFromState($this->state);

  # check if MINGROUPSIZE constraint is not satisfied
  # if so action are deleted
  for($i = 0; $i < MAXSEQUENCE; $i++){
        foreach($act_map[$i] as $act => $actUsers) {
            if(count($actUsers) < $MINGROUPSIZE){
              foreach($act_map[$i][$act] as $act2 => $act2user){
                $this->delete_activity($act2user,$i);
              }
              unset($act_map[$i][$act]);
            }
        }
  }


  #try to reassign activites if possible
  #select the best among available
  for($i = 0; $i < MAXSEQUENCE; $i++){
      for($j = 0; $j < $USERSN; $j++){
        if(!array_key_exists($j, $this->state[$i][STATE_ACT])){

          foreach ($act_map[$i] as $act => $actUsers) {
            if (count($actUsers) == $MAXGROUPSIZE) {
              unset($act_map[$i][$act]);//cancella dalla mappa, cosi' non associamo quest'azione
            }else{
              $actStartTime = $this->state[$i][STATE_START][$actUsers[0]];
              if ($this->overlap($j,$act,$actStartTime,$i) === NULL) {
                $actUsers[] = $j;
                $this->state[$i][STATE_ACT][$j] = $act;
                $this->state[$i][STATE_START][$j] = $this->state[$i][STATE_START][$actUsers[0]];
                break;
              }
            }
          }
        }
      }
    }      
  }//end function move
}//end classe GROUPME problem

  function checkGlobalOverlapByState($state){

    $activities = $GLOBALS['activities'];
    $distances = $GLOBALS['distances'];
    for ($i=1; $i < MAXSEQUENCE; $i++) { 
     // var_dump($state);

      $users = $state[$i][STATE_ACT];

      $h = $i - 1;
      foreach ($users as $user => $act) {
        $actOfPhaseA = $state[$h][STATE_ACT][$user];
        $actOfPhaseB = $act;

        $actASource = $activities[$h][$actOfPhaseA];
        $actBSource = $activities[$i][$actOfPhaseB];

        if ($user == 7 ) {
        echo "\n =====================  \n ";
        echo "act A($actOfPhaseA) start: {$actASource[ACTIVITY_START]} \n";
        echo "act A($actOfPhaseA) duration: {$actASource[ACTIVITY_DURATION]} \n";
        echo "A B distance:".$distances[$actASource[ACTIVITY_CELL]][$actBSource[ACTIVITY_CELL]]."\n";
        $intervalAB = $actASource[ACTIVITY_START] + $actASource[ACTIVITY_DURATION] + $distances[$actASource[ACTIVITY_CELL]][$actBSource[ACTIVITY_CELL]];
        $startB = $actBSource[ACTIVITY_START];
        echo "intervalAB: $intervalAB \n";
        echo "act B($actOfPhaseB) start: ".$actBSource[ACTIVITY_START]."\n";
        echo "\n =====================  \n ";
        }

        if ($intervalAB > $startB ) {
          if ($user == 7 )
          echo "\nError: Time constraint violated for user $user, activity conflict $actOfPhaseA and $actOfPhaseB, $intervalAB is bigger than $startB \n";
        }

      }
    }
  }


  // Array Exampe
  //$act_map = {
  //  phase1: {
  //      activity1: {1,3,4, ...},
  //      activity2: {2,5,8, ...},
  //      },
  //  phase2: ...
  //}
  //
function get_actMapFromState($state){

  $act_map = array();
  for($i = 0; $i < MAXSEQUENCE; $i++){
    $act_map[$i] = array();
    foreach ($state[$i][STATE_ACT] as $j => $jact) {
      if(array_key_exists($jact,$act_map[$i]))
        $act_map[$i][$jact][] = $j;
      else
        $act_map[$i][$jact] = array($j);
    }
  }
  return $act_map;

}

# generate an state where no activity is assigned to users
function generate_inital_state(){
  $init_state = array();
  for($i = 0; $i < MAXSEQUENCE; $i++){
    $init_state[$i] = array();
    $init_state[$i][STATE_START] =array();
    $init_state[$i][STATE_ACT] = array();
  }
  return $init_state;
}

function print_state($state){
  $USERSN = $GLOBALS['USERSN'];
  $distances = $GLOBALS['distances'];
  $activities = $GLOBALS['activities'];

  $USERSN = $GLOBALS['USERSN'];
  $GROUPSN = $GLOBALS['GROUPSN'];
  $MAXGROUPSIZE = $GLOBALS['MAXGROUPSIZE'];
  $MINGROUPSIZE = $GLOBALS['MINGROUPSIZE'];
  
  echo "\n---------- Solution -----------\n";
  for($i = 0; $i < $USERSN; $i++){
    echo "\nUser ".$i.": ";
    $weight = 0;
    $prev_act = NULL;
    $prev_phase = NULL;
    for($j = 0; $j < MAXSEQUENCE; $j++){
      //should be key in array - tong
      if (array_key_exists($i, $state[$j][STATE_ACT])) {
        $act = $state[$j][STATE_ACT][$i];
        $start = $state[$j][STATE_START][$i];
        $tmpidAct = $activities[$j][$act][ACTIVITY_TYPE];

        $weight += $preferences[$i][$tmpidAct];

        echo "\tactivity ".$act." from ".$start." to ".($start + $activities[$j][$act][ACTIVITY_DURATION]);
        if ($prev_act != NULL) {
          echo "\ttraveling distance  ".$distances[$activities[$prev_phase][$prev_act][ACTIVITY_CELL]][$activities[$j][$act][ACTIVITY_CELL]];
        }else{
          echo "";
          $prev_act = $act;
          $prev_phase = $j;
          echo "\t\tPreferences: " + $weight;
        }
      }
    }
  }

  echo "\n\n---------- Act_Map -----------\n\n";

  $actMap = get_actMapFromState($state);
  for ($i=0; $i < MAXSEQUENCE; $i++) { 
    $groupCount = 0;
    $userCount = 0;
    echo "Phase $i: \n";
    $phaseActs = $actMap[$i];
    foreach ($phaseActs as $act => $actUsers) {
      echo "Acivity:$act done by ".count($actUsers)." users \n";
      $groupCount++;
      $userCount += count($actUsers);
    }
    echo "total groups: $groupCount, total users: $userCount\n";
  }
 
  echo "\n---------- Requirments -----------\n";
  echo "\nPeople:$USERSN  Groups:$GROUPSN  GroupMax:$MAXGROUPSIZE  GroupMin:$MINGROUPSIZE \n";

}


$best_solution = NULL;
$best_value = PHP_INT_MAX;
$metric = array();
for($rep = 0; $rep < 1; $rep++){
  srand((double)microtime()*1000000);

  echo "Start iteration ".$rep."\n";

  #init_state = generate_best_state()
  #problem = GroupMeProblem(init_state)
  #print "Initial metric value: " + str(problem.energy())    
  #print_state(init_state)
  #exit(0)
  
  $init_state = generate_inital_state();
  $problem = new GroupMeProblem($init_state);
  
  $problem->updates = 60;   # Number of updates (by default an update prints to stdout)

  #print "Trying to find automatic schedule",
  #auto_schedule = problem.auto(minutes=1)
  #print "Scedule for annealing",
  #print auto_schedule
  #problem.set_schedule(auto_schedule)

  $problem->Tmax = 2;  # Max (starting) temperature
  $problem->Tmin = 1;     # Min (ending) temperature
  $problem->steps = 2000;   # Number of iterations
  //$problem->steps = 100000;   # Number of iterations
  
  //solution, metric[rep] = problem->anneal()
  $rlt = $problem->anneal();
  $solution =  $rlt[0];
  $metric[$rep] =  $rlt[1];
  if($metric[$rep] < $best_value){
    //echo " \n  xx \n";
    $best_value = $metric[$rep];
    $best_solution = $solution;
  }
}
print_state($best_solution);

echo "\n";
echo "Best solution metric function value: ".$best_value."\n\n";

checkGlobalOverlapByState($best_solution);

var_dump($activities[1][2815][ACTIVITY_START]);
// echo "Metric vector: \n";
// var_dump($metric);


?>
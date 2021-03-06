<?php

    try{
        $JACKED->loadDependencies(array('Syrup'));
        
        $exuser = $JACKED->Syrup->User->find(array('email' => $_POST['inputEmail']));
        if(!$exuser){
            $user = $JACKED->Syrup->User->create();
            $user->email = $_POST['inputEmail'];
            $user->save();
            $userid = $user->guid;
        }else{
            $exuser = $exuser[0];
            $userid = $exuser->guid;
        }

        $ticket = $JACKED->Syrup->Ticket->create();
        if(isset($_POST['inputID'])){
            $exid = $JACKED->Syrup->Ticket->find(array('guid' => $_POST['inputID']));
            if($exid){
                throw new Exception('Ticket ID is already in use.');
            }
            $ticket->guid = $_POST['inputID'];
        }
        $ticket->User = $userid;
        $ticket->Promotion = $_POST['inputPromotion'];
        $ticket->single_use = (isset($_POST['inputSingleUse']) && $_POST['inputSingleUse'] == "True")? 1 : 0;
        $ticket->save();
        $JACKED->Sessions->write('admin.success.addticket', 'Ticket <strong>' . $ticket->guid . '</strong> added succesfully.');
    }catch(Exception $e){
        $JACKED->Sessions->write('admin.error.addticket', $e->getMessage());
    }

    include('tickets.php');

?>
<?php
session_start();
include_once ('config.php');
$MathAnswer = 0;
function generate_newMath(){
	global $MathAnswer;
	$randA = mt_rand(1,10);
	$randB = mt_rand(1,10);
	$MathAnswer = $randA + $randB;
	$_SESSION["Math"] = "Solve this ". $randA." + ".$randB." = ?";
	$_SESSION["randAB"] = $MathAnswer;
}

if ($faucet_enable != 1) {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => '<code>Sorry! Faucet is disable, try again later.</code>.')));
}

## Generate new math problem
generate_newMath();

## validate address
if (!valid_address(trim($_POST['WrkzAddress']))) {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => '<code>Incorrect wallet address</code>')));
}

## check if input is faucet wallet
if (strcmp($faucetAddress,trim($_POST['WrkzAddress']))==0) {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => '<code>That is the faucet\'s wallet address!</code>')));
}

## Check the time now
$timeNow = microtime(true);

//object oriented style (recommended)
$mysqli = new mysqli($sqlIP,$sqluser,$sqlpassword,$sqlDB);
//Output any connection error
if ($mysqli->connect_error) {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => '<code>Connection errors. Try again later.</code>.')));
}

//MySqli Select Query for input wallet
$query = "SELECT * FROM wrkzcoin_faucet WHERE wallet='".trim($_POST['WrkzAddress'])."' ORDER BY id DESC LIMIT 1";
$result = mysqli_query($mysqli, $query);
$row = mysqli_fetch_assoc($result);

//MySqli Select Query for last record in database
$query = "SELECT * FROM wrkzcoin_faucet ORDER BY id DESC LIMIT 1";
$result = mysqli_query($mysqli, $query);
$row2 = mysqli_fetch_assoc($result);

## Select session
if (isset($_SESSION["Session"]) && !empty($_SESSION["Session"])) {
    $query = "SELECT * FROM wrkzcoin_faucet WHERE Session='".$_SESSION["Session"]."' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($mysqli, $query);
    $row3 = mysqli_fetch_assoc($result); // get last record only
    if (!empty($row3)) {
    ## If same session was saved value but different wallet.
		if (microtime(true) - $row3['lastDateGet'] < $sameWalletDuration ) {
			echo(json_encode(array('res1' => $_SESSION["Math"],'res2' => "You requested once less than ".secondsToTime($sameWalletDuration)." Wait for another: ". secondsToTime(intval(-microtime(true) + $row3['lastDateGet'] + $sameWalletDuration)))));
			die();
        }
    }
}

## Check record IP. If it is behind cloudflare, shall use set_real_ip_from #
$query = "SELECT * FROM wrkzcoin_faucet WHERE lastip='".$_SERVER["REMOTE_ADDR"]."' ORDER BY id DESC LIMIT 1";
$result = mysqli_query($mysqli, $query);
$row4 = mysqli_fetch_assoc($result); // get last record only
if (!empty($row4)) {
    // If same IP save value but different wallet.
    if (microtime(true) - $row4['lastDateGet'] < $sameWalletDuration ) {
		echo(json_encode(array('res1' => $_SESSION["Math"],'res2' => "You requested once less than ".secondsToTime($sameWalletDuration)." Wait for another: ". secondsToTime(intval(-microtime(true) + $row4['lastDateGet'] + $sameWalletDuration)))));
		die();
	}
}

// $sameWalletDuration
if (!empty($row)) {
    if (microtime(true) - $row['lastDateGet'] < $sameWalletDuration ) {
		echo (json_encode(array('res1' => $_SESSION["Math"],'res2' => "This wallet requested once less than ".secondsToTime($sameWalletDuration)." Wait for another: ". secondsToTime(intval(-microtime(true) + $row['lastDateGet'] + $sameWalletDuration)))));
		die();
	}
}
// $faucetRefreshtime
if (!empty($row2)) {
    if (microtime(true) - $row2['lastDateGet'] < $faucetRefreshtime ) {
		die(json_encode(array('res1' => $_SESSION["Math"],'res2' => "The faucet is not available yet. Wait for another: ". secondsToTime(intval(-microtime(true) + $row2['lastDateGet'] + $faucetRefreshtime)))));
    }
}

// getBalance
// Use curl command to test before using php: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $rcp_server);
curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"jsonrpc\": \"2.0\", \"id\": \"test\",\"method\":\"getBalance\"}");
curl_setopt($ch, CURLOPT_POST, 1);

$headers = array();
$headers[] = "Accept: application/json";
$headers[] = "Content-Type: application/x-www-form-urlencoded";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

$data_json = json_decode($result, true);

$availableBalance = $data_json['result']['availableBalance'];

if (curl_errno($ch)) {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => "<code>Sorry! Internal error, try again later.</code>.")));
}
curl_close ($ch);

## If error availableBalance is not numeric
if (!is_numeric($availableBalance)) {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => "<code>Sorry! Internal error with wallet daemon, try again later.</code>.")));
}
####

### If available balance is less than $minbalance
if ($minbalance > $availableBalance/$coinUnit) {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => "<code>Sorry! There is not enough balance for faucet right now. We need more donations to feed.</code>.")));
}

## check if math random is correct
if (filter_var(trim($_POST['mathequation']), FILTER_VALIDATE_INT)) {
	$MathAnswer = (int)$_SESSION["randAB"];
    if ((int)trim($_POST['mathequation']) - $MathAnswer != 0) {
		echo (json_encode(array('res1' => $_SESSION["Math"],'res2' => '<code>Incorrect math result!</code>')));
		die();
	} else {
		// Correct math result here
		// Let's random coin value:
		$payYou = mt_rand($randomlow,$randomhigh);
		$sendPayment = sendWRKZ($faucetAddress, trim($_POST['WrkzAddress']), $payYou*$coinUnit, $fee*$coinUnit);
		if (!empty($sendPayment)) {
			$InsertWallet['wallet'] = trim($_POST['WrkzAddress']);
			$InsertWallet['lastDateGet'] = intval(microtime(true));
			$InsertWallet['lastPaid'] = $payYou*$coinUnit;
			$InsertWallet['lastip'] = $_SERVER['REMOTE_ADDR'];
			$InsertWallet['Session'] = $sendPayment;

			$query = "INSERT INTO wrkzcoin_faucet (wallet, lastDateGet, lastPaid, lastip, Session) VALUES(?, ?, ?, ?, ?)";
			$statement = $mysqli->prepare($query);

			//bind parameters for markers, where (s = string, i = integer, d = double,  b = blob)
			$statement->bind_param('sssss', $InsertWallet['wallet'], $InsertWallet['lastDateGet'], $InsertWallet['lastPaid'], $InsertWallet['lastip'], $InsertWallet['Session']);
			if($statement->execute()){
				// Save session for transaction id
				$_SESSION["Session"] = $sendPayment;
				die(json_encode(array('res1' => $_SESSION["Math"],'res2' => "You get paid ".$payYou.$coinName. ". Transaction id: <a href='?hash=".$sendPayment."#blockchain_transaction'>".$sendPayment."</a>")));
			} else {
				die(json_encode(array('res1' => $_SESSION["Math"],'res2' => "Internal error. Please report to us. Error code 2003.")));
			}
		} else {
			die("Internal error. Please report to us. Error code 1003.");
		}
	}
} else {
	die(json_encode(array('res1' => $_SESSION["Math"],'res2' => '<code>Invalid math result!</code>')));
}

?>

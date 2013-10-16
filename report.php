<?

require_once "RestClient.php";

class JIRA
{
	public function __construct()
	{
		$config = parse_ini_file("config.ini");

		$this->client = new RestClient($config['user'], $config['pass'], $config['jira']);
	}

	public function getBoards()
	{
		return $this->client->get("greenhopper/latest/rapidview");
	}

	public function getSprints($boardId)
	{
		return $this->client->get("greenhopper/latest/sprints/$boardId");
	}

	public function getSprint($sprintIds)
	{
		// $resp = $this->client->get("api/latest/search", array("jql"=>"sprint=$sprintId", "fields" => "id,key,summary"));
		return $this->client->get("api/latest/search", array("jql"=>"sprint in ($sprintIds)"));
	}
}

$jira = new JIRA();

if(isset($_REQUEST['sprintIds'])) {
	$issues = $jira->getSprint($_REQUEST['sprintIds']);
	header("content-type: application/json");
	echo $issues->ResponseText;

} elseif(isset($_REQUEST['boardId'])) {

	$sprints = $jira->getSprints($_REQUEST['boardId']);
	header("content-type: application/json");
	echo $sprints->ResponseText;

} elseif(isset($_REQUEST['boards'])) {

	$boards = $jira->getBoards();
	header("content-type: application/json");
	echo $boards->ResponseText;

}

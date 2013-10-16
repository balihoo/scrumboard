<!DOCTYPE html>
<html ng-app="ScrumBoard">
	<head>

		<script src="libs/angular/js/angular.min.js" type="text/javascript"></script>
		<script src="libs/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
		<script src="libs/toastr/toastr.min.js" type="text/javascript"></script>
		<script src="libs/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>

		<script src="js/controller.js" type="text/javascript"></script>

		<link type="text/css" rel="stylesheet" href="libs/bootstrap/css/bootstrap-responsive.css">
		<link type="text/css" rel="stylesheet" href="libs/bootstrap/css/bootstrap.css">
		<link type="text/css" rel="stylesheet" href="libs/toastr/toastr.min.css">

		<link href="img/balihoo_icon.gif" rel="shortcut icon" type="image/gif">

	</head>
<body ng-controller="boardController" ng-init="init()">
<div style="vertical-align:middle;margin:4px;">
	<span class="span7 pull-right text-right">
		<i id='version_refresh' class='icon-repeat'></i>
		<select id="version_selector" class="btn btn-mini" ng-selected="board" ng-model="board" ng-options="c.id as c.name for c in boards" ng-change="selectBoard()"></select>
		<select id="sprint_selector" class="btn btn-mini" ng-selected="sprint" ng-model="sprint" ng-options="c for c in sprints" ng-change="selectSprint()"></select>
		<select id="subsprint_selector" class="btn btn-mini" ng-selected="subsprint" ng-model="subsprint" ng-options="c.id as c.name for c in subsprints" ng-change="selectSubSprint()"></select>
	</span>
	<img src="img/balihooLogo.png" />
	<b> SCRUM </b>
</div>


<div class="main-frame">
	<div class='progress al' ng-show="issues">
		<div class='bar' title='Todo {{points_todo}}' style='width: {{percent_todo}}%'></div>
		<div class='bar bar-info' title='Doing {{points_doing}}' style='width: {{percent_doing}}%'></div>
		<div class='bar bar-warning' title='Review {{points_review}}' style='width: {{percent_review}}%'></div>
		<div class='bar bar-danger' title='Testing {{points_testing}}' style='width: {{percent_testing}}%'></div>
		<div class='bar bar-success' title='Done {{points_done}}' style='width: {{percent_done}}%'></div>
	</div><br/>
	<table class="table-condensed">
		<tr ng-repeat="issue in issues | orderBy:'fields.customfield_10104'">
			<td><img src="{{issue.fields.priority.iconUrl}}" title="{{issue.fields.priority.name}}"/></td>
			<td><img src="{{issue.fields.issuetype.iconUrl}}" title="{{issue.fields.issuetype.name}}"/></td>
			<td><a href="https://jira.balihoo.local/browse/{{issue.key}}" target="jira">{{issue.key}}</a></td>
			<td><div class="span6">{{issue.fields.summary}}</div></td>

			<td><img src="{{issue.fields.assignee.avatarUrls.16x16}}" title="{{issue.fields.assignee.displayName}}"/></td>

			<td>
				<div class='progress span1' class="al">
				<div class="bar" title="Todo" style="width: 20%" ng-show="progressLevel(''+issue.fields.status.id, ['1', '4', '3', '10010', '5', '6'])"></div>
				<div class="bar bar-info" title="Doing" style="width: 20%" ng-show="progressLevel(issue.fields.status.id, ['3', '10010', '5', '6'])"></div>
				<div class="bar bar-warning" title="Review" style="width: 20%" ng-show="progressLevel(issue.fields.status.id, ['10010', '5', '6'])"></div>
				<div class="bar bar-danger" title="Testing" style="width: 20%" ng-show="progressLevel(issue.fields.status.id, ['5', '6'])"></div>
				<div class="bar bar-success" title="Done" style="width: 20%" ng-show="progressLevel(issue.fields.status.id, ['6'])"></div>
				</div>
			</td>
			<td>
				<span class="badge badge-inverse">{{issue.fields.customfield_10020}}</span>
			</td>
			<td>
				<span class="label label-important" ng-show="hoursNeeded(issue)" title="{{hoursNeeded(issue)}}">NEEDS HOURS</span>
				<span ng-show="!hoursNeeded(issue)">{{secondsToHours(issue.fields.timespent)}}</span>
			</td>
			<td>
				<span class="label label-warning" ng-show="!categorized(issue)">UNCATEGORIZED</span>
				<span class="label label-info" ng-show="categorized(issue)">{{categorized(issue)}}</span>
			</td>

		</tr>
	</table>
	<br/>
	<div>
		<span>ROADMAP {{category_roadmap}} ({{category_roadmap_percent}}%)</span><br/>
		<span>MAINTENANCE {{category_maintenance}} ({{category_maintenance_percent}}%)</span><br/>
		<span>UNCATEGORIZED {{category_none}} ({{category_none_percent}}%)</span>
	</div>
</div>
</body>
</html>

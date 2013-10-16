var app = angular.module('ScrumBoard', []);

toastr.options = {
	"closeButton" : true,
	"positionClass" : "toast-bottom-right"
};

app.config(
	function($routeProvider) {
		$routeProvider
			.when("/board/:boardId", {action : "board", reloadOnSearch: false})
			.when("/sprint/:sprintId", {action : "sprint", reloadOnSearch: false})
			.when("/subsprint/:subSprintId", {action : "subsprint", reloadOnSearch: false})
			.otherwise({redirectTo : "/"})
		;
	}
);

app.controller('boardController', function($scope, $route, $http, $location) {

	$scope.render = function() {
		var current = $route.current;

		if($route.current.action == "board") {
			$scope.board = current.params.boardId;
			$scope.sprint = false;
			$scope.subsprint = false;
		} else if($route.current.action == "sprint") {
			$scope.sprint = current.params.sprintId;
			$scope.subsprint = false;
		} else if($route.current.action == "subsprint") {
			$scope.subsprint = current.params.subSprintId;
		}
		$scope.loadBoard();

	};

	$scope.$on(
		"$routeChangeSuccess",
		function($currentRoute, $previousRoute) {
			$scope.render();
		}
	);


	$scope.init = function() {
		$scope.getBoards();
	};

	$scope.computeStats = function() {

		$scope.category_totals = {
			"Maintenance" : 0.0
			,"Roadmap" : 0.0
			,"Uncategorized" : 0.0
		};

		$scope.point_totals = {
			"In Progress" : 0.0
			,"Open" : 0.0
			,"Reopened" : 0.0
			,"Dev Review" : 0.0
			,"Closed" : 0.0
			,"Ready For AT" : 0.0
			,"Resolved" : 0.0
		};

		$scope.point_total = 0.0;

		for(v in $scope.issues) {
			var issue = $scope.issues[v];
			$scope.point_totals[issue.fields.status.name] += issue.fields.customfield_10020;
			$scope.point_total += issue.fields.customfield_10020;

			if(issue.fields.customfield_10070) { // "Point allocation"
				$scope.category_totals[issue.fields.customfield_10070.value] += issue.fields.customfield_10020;
			} else {
				$scope.category_totals["Uncategorized"] += issue.fields.customfield_10020;
			}
		}

		$scope.points_todo = $scope.point_totals["Open"] + $scope.point_totals["Reopened"];
		$scope.points_doing = $scope.point_totals["In Progress"];
		$scope.points_review = $scope.point_totals["Dev Review"];
		$scope.points_testing = $scope.point_totals["Ready For AT"] + $scope.point_totals['Resolved'];
		$scope.points_done = $scope.point_totals["Closed"];

		$scope.percent_todo = ($scope.points_todo / $scope.point_total) * 100.0;
		$scope.percent_doing = ($scope.points_doing / $scope.point_total) * 100.0;
		$scope.percent_review = ($scope.points_review / $scope.point_total) * 100.0;
		$scope.percent_testing = ($scope.points_testing / $scope.point_total) * 100.0;
		$scope.percent_done = ($scope.points_done / $scope.point_total) * 100.0;

		$scope.category_roadmap = $scope.category_totals['Roadmap'];
		$scope.category_maintenance = $scope.category_totals['Maintenance'];
		$scope.category_none = $scope.category_totals['Uncategorized'];

		$scope.category_roadmap_percent = ($scope.category_roadmap / $scope.point_total) * 100.0;
		$scope.category_maintenance_percent = ($scope.category_maintenance / $scope.point_total) * 100.0;
		$scope.category_none_percent = ($scope.category_none / $scope.point_total) * 100.0;

	};

	$scope.getSprint = function(ids) {
		$scope.issues = new Array();
		$http.get('report.php', { params: {sprintIds: ids.join()}}).
			success(function(data) {
				        $scope.issues = data.issues;
						$scope.computeStats();
			        }).
			error(function(error) {
				      console.log("the error is"+error);
			      });
	};

	$scope.selectBoard = function() {

		$location.path("board/"+$scope.board);

	};

	$scope.selectSprint = function() {

		$location.path("sprint/"+$scope.sprint);

	};

	$scope.selectSubSprint = function() {

		$scope.sprint = $scope.subsprint.psprint;
		$location.path("subsprint/"+$scope.subsprint);

	};

	$scope.loadSprint = function() {
		for(var s in $scope.subsprints) {
			sprint = $scope.subsprints[s];
			if(sprint.id == $scope.subsprint) {
				$scope.subsprint = sprint.id;
				$scope.sprint = sprint.psprint;
			}
		}
		for(var s in $scope.sprints) {
			if(s == $scope.sprint) {
				$scope.sprint = s;
			}
		}
		if($scope.subsprint) {
			$scope.getSprint([$scope.subsprint]);
		} else {
			var want = [];
			for(c in $scope.subsprints) {
				var sp = $scope.subsprints[c];
				if(sp.psprint == $scope.sprint) {
					want.push(sp.id);
				}
			}

			$scope.getSprint(want);
		}
	};

	$scope.getBoard = function(id) {
		$scope.subsprints = new Array();
		$scope.sprints = new Array();
		$scope.issues = new Array();
		$http.get('report.php', { params: {boardId : id}}).
			success(function(sprints) {
				        if(sprints.sprints) {
					        $scope.subsprints = sprints.sprints.reverse();

					        for(v in $scope.subsprints) {
						        var sp = $scope.subsprints[v];
						        var parts = sp.name.split(" ");
						        sp.psprint = parts[0];
						        if($.inArray(parts[0], $scope.sprints) == -1) {
							        $scope.sprints.push(parts[0])
						        }

					        }

				        }

			            if(!$scope.sprint) {
				            $scope.sprint = $scope.sprints[0];
			            }

			            $scope.loadSprint();
			        }).
			error(function(error) {
				      console.log("the error is"+error);
			      });
	};

	$scope.selectBoard = function() {
		$location.path("board/"+$scope.board);
	};

	$scope.loadBoard = function() {
		for(var s in $scope.boards) {
			board = $scope.boards[s];
			if(board.id == $scope.board) {
				$scope.board = board.id;
			}
		}
		$scope.getBoard($scope.board);
	};

	$scope.getBoards = function() {
		$scope.boards = new Array();
		$scope.subsprints = new Array();
		$scope.sprints = new Array();
		$scope.issues = new Array();
		$http.get('report.php', { params: {boards : true}}).
			success(function(data) {
			            var devBoard = null;
				        for(v in data.views) {
					        var view = data.views[v];
					        if(view.name == "Development ") {
						        devBoard = view.id;
					        }
					        if(view.sprintSupportEnabled) {
						        // We'll ignore boards that don't handle sprints
						        $scope.boards.push(view);
					        } else {
					        }

				        }

				        if(!$scope.board) {
					        $scope.board = devBoard;
				        }

			            $scope.loadBoard();

			        }).
			error(function(error) {
				      console.log("the error is"+error);
			      });
	};

	$scope.progressLevel = function(status, statii) {
		return $.inArray(status, statii) > -1;
	};

	$scope.hoursNeeded = function(issue) {
		if(issue.fields.customfield_10031 && !issue.fields.timespent) {
			return "Work for "+issue.fields.customfield_10031.value;
		}
		if(!issue.fields.customfield_10020) {
			return "No points";
		}

		return false;
	};

	$scope.categorized = function(issue) {
		if(issue.fields.customfield_10070) { // "Point allocation"
			return issue.fields.customfield_10070.value;
		}

		return false;
	};

	$scope.secondsToHours = function(seconds) {
		hours = seconds / 60.0 / 60.0;
		return hours == 0 ? "" : hours;
	}


});
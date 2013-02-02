<div id="app" ng-app="Friends"
	ng-init="tab=0;">

	<h1 class="heading">Friends</h1>



	<div class="tabnav">	
		<a href="#" ng-click="tab=0" class="selectedTab-[[tab==0]]">Your Friends</a>
		<a href="#" ng-click="tab=1" class="selectedTab-[[tab==1]]">Add Friends</a>
		<a href="#" ng-click="tab=2" class="selectedTab-[[tab==2]]">Accept Friend Requests</a>
	</div>
		
	
	<div class="app-body">	
	<!-- friends -->
	<div ng-show="tab==0" ng-controller="FriendshipController">
			<span ng-repeat="friendship in friendships">
				[[friendship]]
			</span>
			<p ng-hide="friendships">
				You do not have any friends.
			</p>
		
	</div>



	<div  ng-controller="FRController">
		<div ng-show="tab==1" class="">
			<form class="centered">
				<input type="text" placeholder="Enter username" ng-model="recipient">
				<button ng-click="createFriendshipRequest(recipient)">Request Friendship</button>
			</form>

		<!--sent requests -->
			<span ng-repeat="friendshipRequest in sentFriendshipRequests">
				[[friendshipRequest]]
			</span>
			<p ng-hide="sentFriendshipRequests">
				You are not waiting on any friend requests.
			</p>
		</div>

		<!--received requests -->
		<div ng-show="tab==2" class="">
			<span ng-repeat="friendshipRequest in receivedFriendshipRequests">
				[[friendshipRequest]]
			</span>
			<p ng-hide="receivedFriendshipRequests">
				You have responded to all your friend requests.
			</p>
		</div>
	</div>

	</div>
</div>




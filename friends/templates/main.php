<div id="app" ng-app="Friends"
	ng-init="name='{{somesetting}}'">

	<h1 class="heading">Friends</h1>

	<!--<p>The URL Parameter for the index page is: {{test}}</p>-->

	<p ng-show="name">Welcome home [[name | leetIt]]!</p>

	

	<div ng-controller="FRController">
		<div>
			<form class="centered">
				<input type="text" placeholder="Enter username" ng-model="recipient">
				<button ng-click="createFriendshipRequest(recipient)">Request Friendship</button>
			</form>

		<!--sent requests -->
			<span ng-repeat="friendshipRequest in sentFriendshipRequests">
				[[friendshipRequest]]
			</span>
		</div>

		<!--received requests -->
		<div>
			<span ng-repeat="friendshipRequest in receivedFriendshipRequests">
				[[friendshipRequest]]
			</span>
		</div>
	</div>

		
	
	
	<!-- friends -->
	<div ng-controller="FriendshipController">
		<br /><br />
		<p>Your friends are: </p>
			<span ng-repeat="friendship in friendships">
				[[friendship]]
			</span>
		
	</div>
</div>




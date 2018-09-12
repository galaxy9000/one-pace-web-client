import React from "react";
import ReactDOM from "react-dom";
import {
	Router,
	Route,
	hashHistory,
	IndexRoute,
} from "react-router";
import "../../index.css";
import "babel-polyfill";
import Watch from "./Watch";
import About from "./About";
import Torrents from "./Torrents";
import Progress from "./Progress/Progress";
import SupportUs from "./SupportUs";
import Layout from "./Layout";

ReactDOM.render(
	<Layout>
		<Router history={hashHistory}>
			<Route path="/">
				<IndexRoute component={Watch} />
				<Route name="torrents" path="/torrents" component={Torrents} />
				<Route name="about" path="/about" component={About} />
				<Route name="progress" path="/progress" component={Progress} />
				<Route name="supportus" path="/supportus" component={SupportUs} />
			</Route>
		</Router>
	</Layout>
	, document.getElementById("reactentry")
);

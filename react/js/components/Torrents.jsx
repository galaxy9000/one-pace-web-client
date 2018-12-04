import React from "react";
import NetworkHandler from "../NetworkHandler";
import Moment from "moment";
import { Glyphicon } from "react-bootstrap";
import LocalStorageUtils from "../LocalStorageUtils";

export default class Torrents extends React.Component {
	constructor(props) {
		super(props);
		let sortField = LocalStorageUtils.getSortField();
		if (sortField == null) {
			sortField = "createddate";
		}
		let sortAscending = LocalStorageUtils.getSortAscending();
		if (sortAscending == null) {
			sortAscending = true;
		}
		sortAscending = sortAscending === "true";
		this.state = {
			"releases": [],
			"sortField": sortField,
			"sortAscending": sortAscending,
		};
	}
	componentDidMount() {
		document.title = "One Pace | Torrents";
		NetworkHandler.request("/getreleases.php", null, (responseJson) => {
			let releases = this.sortReleases(responseJson.releases, this.state.sortField, this.state.sortAscending);
			this.setState({ "releases": releases });
		}, null);
	}
	sortReleases = (releases, sortField, sortAscending) => {
		releases = releases.sort((a, b) => sortAscending ?
			(b[sortField] + "").localeCompare(a[sortField], undefined, { "numeric": true, "sensitivity": "base" }) :
			(a[sortField] + "").localeCompare(b[sortField], undefined, { "numeric": true, "sensitivity": "base" })
		);
		return releases;
	}
	sort = (sortField, sortAscending) => {
		const releases = this.sortReleases(this.state.releases, sortField, sortAscending);
		LocalStorageUtils.setSortField(sortField);
		LocalStorageUtils.setSortAscending(sortAscending);
		this.setState({ releases, sortField, sortAscending });
	}
	render() {
		const { sortField, sortAscending } = this.state;
		const sortArrow = sortAscending ? <Glyphicon glyph="arrow-up" /> : <Glyphicon glyph="arrow-down" />;
		return (
			<div className="with-padding">
				<h2>Torrents</h2>
				<table className="releases vl">
					<thead>
						<tr>
							<th onClick={() => this.sort("name", sortField == "name" && !sortAscending)}>Name {sortField == "name" && sortArrow}</th>
							<th className="date" onClick={() => this.sort("createddate", sortField == "createddate" && !sortAscending)}>Date {sortField == "createddate" && sortArrow}</th>
							<th>Magnet</th>
							<th>AnimeTosho</th>
						</tr>
					</thead>
					<tbody>
						{
							this.state.releases.map((i, index) => {
								const createddate = Moment.unix(i.createddate).format("YYYY-MM-DD");
								return (
									<tr key={index}>
										<td className="name"><a href={i.torrent}>{i.name}</a></td>
										<td>{createddate}</td>
										<td><a href={i.magnet}>Magnet</a></td>
										<td><a href={"https://animetosho.org/search?q=" + i.crc32} target="_blank">AT</a></td>
									</tr>
								);
							})
						}
					</tbody>
				</table>
			</div>
		);
	}
}

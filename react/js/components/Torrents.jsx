import React from "react";
import NetworkHandler from "../NetworkHandler";
import Moment from "moment";
import Layout from './Layout';

export default class Torrents extends React.Component {
  state = {
    "releases": [],
  }
  componentDidMount() {
    document.title = "One Pace | Torrents";
    NetworkHandler.request("/getreleases.php", {}, (responseJson) => {
      let releases = responseJson.releases;
      this.setState({ "releases": releases });
    }, null);
  }
  render() {
    return (
      <div>
        <Layout>
          <div className="card">
            <h2>Torrents</h2>
            <table className="releases vl">
              <thead>
                <th>Name</th>
                <th>Date</th>
                <th>Magnet</th>
                <th>AnimeTosho</th>
              </thead>
              <tbody>
                {
                  this.state.releases.map((i) => {
                    const createddate = Moment.unix(i.createddate).format("YYYY-MM-DD");
                    return (
                      <tr key={"release-" + i.crc32}>
                        <td className="name" width="80%"><a href={i.torrent}>{i.name}</a></td>
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
        </Layout>
      </div>
    );
  }
}

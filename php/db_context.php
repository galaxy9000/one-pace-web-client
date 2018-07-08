<?php
include_once 'config.php';
include_once 'logger.php';

class db_context {
	private $connection;
	/* Help methods */
	function connect() {
		$socket = null;
		if(!DEBUG) {
			$socket = DB_SOCKET;
		}
		$this->connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT, $socket);
		if (mysqli_connect_errno()) {
			log_error('Failed to connect to MySQL: ' . mysqli_connect_error());
		}
		$this->connection->set_charset(DB_CHARSET);
	}
	function disconnect() {
		mysqli_close($this->connection);
	}
	function get_param_type($value) {
		if(is_int($value)) {
			return "i";
		} else if (is_double($value)) {
			return "d";
		} else if (is_string($value)) {
			return "s";
		} else if (is_bool($value)) {
			return "b";
		} else if ($value == null) {
			return "s";
		}
	}
	function bind_param($stmt, $params) {
		$types = "";
		$param_keys = [];
		foreach($params as $key => $value) {
			$param_keys[] = $value;
			$types .= $this->get_param_type($value);
		}
		$stmt->bind_param($types, ...$param_keys);
	}
	function get_result($stmt) {
		if(!$stmt->execute()) {
			log_error($stmt->error);
		}
		if(!$result = $stmt->get_result()) {
			log_error($stmt->error);
		}
		$stmt->close();
		$rows = [];
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		return $rows;
	}
	function get_single($stmt) {
		$rows = $this->get_result($stmt);
		if(sizeof($rows) > 0){
			return $rows[0];
		} else {
			return null;
		}
	}
	function execute($stmt) {
		if(!$stmt->execute()) {
			log_error($stmt->error);
		}
		$stmt->close();
	}
	function prepare($sql) {
		$connection = $this->connection;
		$stmt = $connection->prepare($sql);
		if($stmt === false) {
			log_error('Wrong SQL: ' . $sql . ' Error: ' . $connection->errno . ' ' . $connection->error, E_USER_ERROR);
		}
		return $stmt;
	}
	function prepare_and_bind($sql, $params = null) {
		$stmt = $this->prepare($sql);
		if($params != null) {
			$this->bind_param($stmt, $params);
		}
		return $stmt;
	}
	function prepare_and_get_result($sql, $params = null) {
		$stmt = $this->prepare_and_bind($sql, $params);
		return $this->get_result($stmt);
	}
	function prepare_and_get_single($sql, $params = null) {
		$stmt = $this->prepare_and_bind($sql, $params);
		return $this->get_single($stmt);
	}
	function prepare_and_execute($sql, $params = null) {
		$stmt = $this->prepare_and_bind($sql, $params);
		$this->execute($stmt);
	}
	/* Operations */
	function create($table, $params) {
		if(sizeof($params) == 0) {
			return false;
		}
		$sql = "insert into ".$table." (";
		foreach($params as $key => $value) {
			if($notfirst) {
				$sql .= ", ";
			} else {
				$notfirst = true;
			}
			$sql .= "`".$key."`";
		}
		$sql .= ") values(";
		$notfirst = false;
		foreach($params as $key => $value) {
			if($notfirst) {
				$sql .= ", ";
			} else {
				$notfirst = true;
			}
			$sql .= "?";
		}
		$sql .= ");";
		$this->prepare_and_execute($sql, $params);
		return true;
	}
	function read($table, $id) {
		return $this->prepare_and_get_single("select * from ".$table." where id = ?", ["id" => $id]);
	}
	function update($table, $id, $params) {
		if(sizeof($params) == 0) {
			return false;
		}
		$sql = "update ".$table." set ";
		foreach($params as $key => $value) {
			if($notfirst) {
				$sql .= ", ";
			} else {
				$notfirst = true;
			}
			$sql .= "`".$key."` = ?";
		}
		$sql .= " where id = ?;";
		$params["id"] = $id;
		$this->prepare_and_execute($sql, $params);
		return true;
	}
	function delete($table, $id) {
		$this->prepare_and_execute("delete from ".$table." where id = ?;", ["id" => $id]);
		return true;
	}

	/* Episode versions */
	function list_episodeversions($user, $episode_id) {
		$rows = $this->prepare_and_get_result(
			"select
				episodeversions.*, issues.id as issue_id, issues.completed as issue_completed, issues.createdby as issue_created_by,
				issues.createddate as issue_createddate, issues.description as issue_description
			from episodeversions
			right join issues on issues.episodeversion_id = episodeversions.id
			where episodeversions.episode_id = ?
			order by major desc, minor desc, issues.completed, issues.createddate
			;"
		);
		$data = [];
		$id = -1;
		$id_index = -1;
		foreach($rows as $row) {
			if($id != $row['id']) {
				$id = $row['id'];
				$data['episodeversions'][] = [
					'id' => $row['id'],
					'major' => $row['major'],
					'minor' => $row['minor'],
					'filename' => $row['filename']
				];
				$id_index++;
			}
			$data['episodeversions'][$id_index]['issues'][] = [
				'id' => $row['issue_id'],
				"description" => $row['issue_description'],
				'createdby' => $row['issue_createdby'],
				'createddate' => $row['issue_createddate'],
				"completed" => $row['issue_completed']
			];
		}
		return $data;
	}

	/* Episodes */
	function update_episode($id, $params) {
		return $this->update("episodes", $id, $params);
	}
	function list_progress_episodes($user) {
		$rows = $this->prepare_and_get_result(
			"select
				(select count(*) from issues where episode_id = episodes.id and issues.completed = false) as issues_total,
				((episodes.hidden is null or episodes.hidden = false) and (released_date is null or released_date > now())) as in_progress,
				(select count(*) from episodes where arcs.id = arc_id and hidden = false and (released_date is null or released_date > now())) > 0 as arc_in_progress,
				episodes.*, arcs.title as arc_title, arcs.chapters as arc_chapters,
				arcs.episodes as arc_episodes, arcs.completed as arc_completed, arcs.resolution as arc_resolution,
				arcs.torrent_hash as arc_torrent_hash, arcs.released as arc_released, arcs.hidden as arc_hidden
			from episodes
			right join arcs on arcs.id = episodes.arc_id
			left join issues on issues.episode_id = episodes.id".
			($user == null || $user['role'] <= 1 ? " where arcs.hidden = false and episodes.hidden = false" : "")
			." group by episodes.id
			order by arc_in_progress desc, in_progress desc, abs(arc_chapters) desc, abs(episodes.chapters)
			;"
		);
		$data = [];
		$arc_id = -1;
		foreach($rows as $row) {
			// Only add a new arc object if the arc id is different from the previous row.
			if($arc_id != $row['arc_id']) {
				// Update the arc_id variable with the latest arc id.
				$arc_id = $row['arc_id'];

				// Set the arc object.
				$data['arcs'][] = [
					'id' => $row['arc_id'],
					'in_progress' => $row['arc_in_progress'],
					'title' => $row['arc_title'],
					'hidden' => $row['arc_hidden'],
					'chapters' => $row['arc_chapters'],
					'in_progress' => false
				];
			}

			// Set the episode object.
			$data['episodes'][] = [
				'id' => $row['id'],
				"crc32" => $row["crc32"],
				"arc_id" => $row['arc_id'],
				'part' => $row['part'],
				'title' => $row['title'],
				'chapters' => $row['chapters'],
				"episodes" => $row["episodes"],
				"resolution" => $row["resolution"],
				"torrent_hash" => $row["torrent_hash"],
				"status" => $row['status'],
				"hidden" => $row['hidden'],
				"in_progress" => $row['in_progress'],
				"released_date" => $row['released_date'] == null ? '' : $row['released_date'],
				"issues_total" => $row['issues_total']
			];
		}
		return $data;
	}

	/* Users */
	function create_user($params) {
		return $this->create("users", $params);
	}
	function update_user($id, $params) {
		return $this->update("users", $id, $params);
	}

	/* Issues */
	function create_issue($params) {
		return $this->create("issues", $params);
	}
	function read_issue($id) {
		return $this->read("issues", $id);
	}
	function update_issue($id, $params) {
		return $this->update("issues", $id, $params);
	}
	function delete_issue($id) {
		return $this->delete("issues", $id);
	}
}

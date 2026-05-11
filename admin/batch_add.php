<?php

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		require "../inv.header.php";
		if(!$user->gotpermission('admin_panel'))
			exit;
		if(file_exists("jobs/pid"))
			exit;
		chdir("..");
		$result = exec("php -f batch_add.php > /dev/null &");
		if($result === false)
			http_response_code(500);
		else
			http_response_code(200);
		exit;
	}

	if(!defined('_IN_ADMIN_HEADER_'))
		exit;

	$count = 0;
	if(!is_dir("jobs"))
		mkdir("jobs");

	function human_filesize($fpath, $decimals = 2)
	{
		$bytes = filesize($fpath);
		$str = "";

		$factor = (int)floor((strlen($bytes) - 1) / 3);
		if ($factor > 0) $sz = 'KMGT';
		$tens_pow = pow(1024, $factor);
		$str = sprintf("%.{$decimals}f", (int)$bytes / $tens_pow);
		if($factor != 0)
			return $str." ".$sz[$factor - 1].'B';
		else
			return "$str B";
	}
?>

	<script>
		var months = ["January", "February", "March", "April", "May", "June", "July", "Autust", "September", "October", "November", "December"];
		var data_files = [];
		var currentPrefix = "";
		var jobsToDisplay = 20;
		var errorsToDisplay = 20;

		function _startJob()
		{
			if(this.classList.contains("disabled"))
				return;
			fetch("batch_add.php", { method: "POST" })
			.then((resp) => {
				if(resp.ok)
				{
					document.querySelector(".banner").classList.replace("idle", "active");
					document.querySelector(".banner button").disabled = true;
					let d = new Date();
					let t = d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();
					document.querySelector(".banner span").innerHTML = `Job started ${months[d.getMonth()]} ${d.getDate()}, ${t}`;
				}
				else
				{
					alert("Error starting job");
				}
			});
		}
		function getDataFile(ele)
		{
			while(!ele.dataset.file && ele !== document.body)
			{
				ele = ele.parentElement;
			}
			return ele.dataset.file;
		}
		function renderImport(job)
		{
			ele = document.createElement("div");
			ele.classList.add("jimp", "collapsable", "closed");
			ele.innerHTML = `<div class="file">${job.filepath.replace(currentPrefix, "")}</div>
			<div class="details">
			<span>Tags: ${job.tags}</span>
			<span>Hash: ${job.hash}</span>
<?php
			echo '<span>Post ID: <a href=\"'.$site_url.'index.php?page=post&s=view&id=${job.id}\">${job.id}</a></span>';
?>
			<span>Title: ${job.title}</span>
			<span>Source: ${job.source}</span>
			<span>Thumbnail successful: ${job.thumbnail}</span>
			</div>`;
			return ele;
		}
		function renderImportPage(num, container)
		{
			container.innerHTML = "";
			let d = data_files[getDataFile(container)];

			let start = (num-1) * jobsToDisplay;
			for(let i = start; i < Math.min(start+jobsToDisplay, d.imports.length); ++i)
			{
				container.appendChild(renderImport(d.imports[i]));
			}

			for(let ele of container.querySelectorAll(".file, .collapse-label"))
			{
				ele.addEventListener("click", function(e) {
					let p = this.parentElement;
					if(p.classList.contains("open"))
						p.classList.replace("open", "closed");
					else
						p.classList.replace("closed", "open");
				});
			}
			for(let ele of container.querySelectorAll(".err-msg a"))
			{
				ele.addEventListener("click", function(e) {
					e.preventDefault();
					open("../" + this.href.substr(this.href.indexOf("index.php")), "_blank");
				});
			}
		}
		function renderError(job)
		{
			ele = document.createElement("div");
			ele.classList.add("jerr", "collapsable", "closed");
			ele.innerHTML = `<div class="file">${job.filepath.replace(currentPrefix, "")}</div>
			<div class="details">
			<span>Link: <a href="../${job.filepath}">link</a></span>
			<span>Error type: ${job.errortype}</span>
			<span class="err-msg">Error message: ${job.errormessage}</span>
			</div>`;
			return ele;
		}
		function renderErrorPage(num, container)
		{
			container.innerHTML = "";
			let d = data_files[getDataFile(container)];

			let start = (num-1) * errorsToDisplay;
			for(let i = start; i < Math.min(start+errorsToDisplay, d.errors.length); ++i)
			{
				container.appendChild(renderError(d.errors[i]));
			}

			for(let ele of container.querySelectorAll(".file, .collapse-label"))
			{
				ele.addEventListener("click", function(e) {
					let p = this.parentElement;
					if(p.classList.contains("open"))
						p.classList.replace("open", "closed");
					else
						p.classList.replace("closed", "open");
				});
			}
		}
		function GetData(json_file, container)
		{
			fetch("jobs/"+json_file)
			.then((resp) => {
				if(resp.ok)
					return resp.json();
			})
			.then((json) => {
				let obj = {};
				obj.imports = [];
				obj.errors = [];
				data_files[json_file] = obj;
				for(let ev of json)
				{
					switch(ev.event)
					{
						case "info":
							currentPrefix = ev.prefix || "";
						break;
						case "import":
							obj.imports.push(ev);
						break;
						case "error":
							obj.errors.push(ev);
						break;
					}
				}
				container.querySelector(".icount").innerHTML = "Imported images: " + obj.imports.length;
				container.querySelector(".ecount").innerHTML = "Errors: " + obj.errors.length;

				if(obj.imports.length > 0)
				{
					renderImportPage(1, container.querySelector(".job-imports .container"));
					if(obj.imports.length > jobsToDisplay)
					{
						container.querySelector(".job-imports .right").classList.remove("inactive");
						container.querySelector(".job-imports .last").classList.remove("inactive");
						setupImportPagination(container);
					}
				}
				else
				{
					container.querySelector(".import-pages").innerHTML = "";
				}

				if(obj.errors.length > 0)
				{
					renderErrorPage(1, container.querySelector(".job-errors .container"));
					if(obj.errors.length > errorsToDisplay)
					{
						container.querySelector(".job-errors .right").classList.remove("inactive");
						container.querySelector(".job-errors .last").classList.remove("inactive");
						setupErrorPagination(container);
					}
				}
				else
				{
					container.querySelector(".error-pages").innerHTML = "";
				}


				//requestAnimationFrame(() => container.classList.replace("closed", "open"));
				container.classList.replace("closed", "open");
			});
		}

		function setupImportPagination(container)
		{
			container.querySelector(".import-pages .first").addEventListener("click", function(e) {
				this.parentElement.dataset.page = 1;
				renderImportPage(1, this.parentElement.parentElement.querySelector(".container"));
				this.parentElement.querySelector(".right").classList.remove("inactive");
				this.parentElement.querySelector(".last").classList.remove("inactive");

				this.parentElement.querySelector(".left").classList.add("inactive");
				this.classList.add("inactive");

				this.parentElement.querySelector(".currentpage").innerHTML = 1;
			});
			container.querySelector(".import-pages .left").addEventListener("click", function(e) {
				let pg = Number(this.parentElement.dataset.page);
				if(pg > 1)
				{
					pg--;
					this.parentElement.dataset.page = pg;
					renderImportPage(pg, this.parentElement.parentElement.querySelector(".container"));
					this.parentElement.querySelector(".right").classList.remove("inactive");
					this.parentElement.querySelector(".last").classList.remove("inactive");
					if(pg == 1)
					{
						this.parentElement.querySelector(".first").classList.add("inactive");
						this.classList.add("inactive");
					}
					this.parentElement.querySelector(".currentpage").innerHTML = pg;
				}
			});
			container.querySelector(".import-pages .right").addEventListener("click", function(e) {
				let pg = Number(this.parentElement.dataset.page);
				let d = data_files[getDataFile(this)];
				let pgMax = Math.ceil(d.imports.length / jobsToDisplay);
				if(pg < pgMax)
				{
					pg++;
					this.parentElement.dataset.page = pg;
					renderImportPage(pg, this.parentElement.parentElement.querySelector(".container"));
					this.parentElement.querySelector(".left").classList.remove("inactive");
					this.parentElement.querySelector(".first").classList.remove("inactive");
					if(pg == pgMax)
					{
						this.parentElement.querySelector(".last").classList.add("inactive");
						this.classList.add("inactive");
					}
					this.parentElement.querySelector(".currentpage").innerHTML = pg;
				}
			});
			container.querySelector(".import-pages .last").addEventListener("click", function(e) {
				let d = data_files[getDataFile(this)];
				let pgMax = Math.ceil(d.imports.length / jobsToDisplay);
				this.parentElement.dataset.page = pgMax;
				renderImportPage(pgMax, this.parentElement.parentElement.querySelector(".container"));
				this.parentElement.querySelector(".left").classList.remove("inactive");
				this.parentElement.querySelector(".first").classList.remove("inactive");

				this.parentElement.querySelector(".right").classList.add("inactive");
				this.classList.add("inactive");

				this.parentElement.querySelector(".currentpage").innerHTML = pgMax;
			});
		}

		function setupErrorPagination(container)
		{
			container.querySelector(".error-pages .first").addEventListener("click", function(e) {
				this.parentElement.dataset.page = 1;
				renderErrorPage(1, this.parentElement.parentElement.querySelector(".container"));
				this.parentElement.querySelector(".right").classList.remove("inactive");
				this.parentElement.querySelector(".last").classList.remove("inactive");

				this.parentElement.querySelector(".left").classList.add("inactive");
				this.classList.add("inactive");

				this.parentElement.querySelector(".currentpage").innerHTML = 1;
			});

			container.querySelector(".error-pages .left").addEventListener("click", function(e) {
				let pg = Number(this.parentElement.dataset.page);
				if(pg > 1)
				{
					pg--;
					this.parentElement.dataset.page = pg;
					renderErrorPage(pg, this.parentElement.parentElement.querySelector(".container"));
					this.parentElement.querySelector(".right").classList.remove("inactive");
					this.parentElement.querySelector(".last").classList.remove("inactive");
					if(pg == 1)
					{
						this.parentElement.querySelector(".first").classList.add("inactive");
						this.classList.add("inactive");
					}
					this.parentElement.querySelector(".currentpage").innerHTML = pg;
				}
			});
			container.querySelector(".error-pages .right").addEventListener("click", function(e) {
				let pg = Number(this.parentElement.dataset.page);
				let d = data_files[getDataFile(this)];
				let pgMax = Math.ceil(d.errors.length / errorsToDisplay);
				if(pg < pgMax)
				{
					pg++;
					this.parentElement.dataset.page = pg;
					renderErrorPage(pg, this.parentElement.parentElement.querySelector(".container"));
					this.parentElement.querySelector(".first").classList.remove("inactive");
					this.parentElement.querySelector(".left").classList.remove("inactive");
					if(pg == pgMax)
					{
						this.parentElement.querySelector(".last").classList.add("inactive");
						this.classList.add("inactive");
					}
					this.parentElement.querySelector(".currentpage").innerHTML = pg;
				}
			});
			container.querySelector(".error-pages .last").addEventListener("click", function(e) {
				let d = data_files[getDataFile(this)];
				let pgMax = Math.ceil(d.errors.length / errorsToDisplay);
				this.parentElement.dataset.page = pgMax;
				renderErrorPage(pgMax, this.parentElement.parentElement.querySelector(".container"));
				this.parentElement.querySelector(".left").classList.remove("inactive");
				this.parentElement.querySelector(".first").classList.remove("inactive");

				this.parentElement.querySelector(".right").classList.add("inactive");
				this.classList.add("inactive");

				this.parentElement.querySelector(".currentpage").innerHTML = pgMax;
			});
		}

		document.addEventListener("DOMContentLoaded", function(e) {
			document.querySelector(".banner button").addEventListener("click", _startJob);
<?php
			if(file_exists("jobs/pid"))
			{
?>
				document.querySelector('.banner').classList.replace('idle', 'active');
				document.querySelector('.banner button').disabled = true;
				document.querySelector('.banner span').innerHTML = "Job started <?php echo date("F j, g:i a", filectime("jobs/pid")); ?>";
<?php
			}
?>

			for(let job of document.querySelectorAll(".job-info"))
			{
				job.addEventListener("click", function(e) {
					let p = this.parentElement;
					if(p.dataset.getflag == 1)
					{
						if(p.classList.contains("closed"))
							p.classList.replace("closed", "open");
						else
							p.classList.replace("open", "closed");
					}
					else
					{
						p.dataset.getflag = 1;
						GetData(p.dataset.file, p);
					}
				});
			}
		});
	</script>
	<style>
	#content {
		display: flex;
	}
	div.sidebar {
		float: unset;
		width: unset;
	}
	#post-list {
		flex: 2;
	}
	.banner {
		border-radius: 7px;
		padding-top: 5px;
		padding-left: 5px;
	}
	.banner.idle {
		background-color: lightgrey;
		border: 3px solid #777;
	}
	.banner.active {
		background-color: skyblue;
		border: 3px solid #0998cd;
	}
	.banner button {
		display: block;
		margin-top: 4px;
		margin-bottom: 6px;
	}
	.job-record {
		margin-top: 1.5em;
		margin-bottom: 1.5em;
		padding-top: 10px;
		padding-bottom: 10px;
		padding-left: 8px;
		border: 4px solid cornflowerblue;
		border-radius: 6px;
	}
	.job-details {
		background-color: cornsilk;
		max-height: 500px;
	}
	.open .job-details {
		margin-top: 1em;
		margin-left: 8px;
	}
	.open .job-errors {
		margin-left: 8px;
	}
	.open .job-imports {
		margin-left: 8px;
	}
	.collapsable > div:first-child::before {
		display: inline-block;
		height: 1rem;
		aspect-ratio: 1;
		content: '\25B2 ';
		margin-right: 8px;
		transition: transform 0.25s ease-in-out;
	}
	.open > div:first-child::before {
		transform: rotate(180deg);
	}
	.closed > div:first-child::before {
		transform: rotate(90deg);
	}
	.collapsable,
	.collapsable > div:last-child {
		transition: height 0.5s ease-in-out;
	}
	.open > div:last-child {
		height: 100%;
		overflow-x: hidden;
		overflow-y: scroll;
	}
	.open > div.details  {
		overflow: auto;
	}
	.closed > div:last-child {
		height: 0px;
		overflow: hidden;
	}
	.details {
		background-color: lightsteelblue;
		max-height: 100px;
	}
	.details > * {
		display: block;
	}
	.jimp,.jerr {
		margin-top: 3px;
		margin-bottom: 3px;
	}
	.jimp.open,
	.jerr.open {
		margin-top: 5px;
		margin-bottom: 5px;
	}
	.open > .file {
		text-decoration: underline;
	}
	.closed .file:hover {
		text-decoration: underline;
	}

	.first, .left, .right, .last {
		text-decoration: underline;
		color: blue;
		cursor: pointer;
	}
	.first.inactive, .left.inactive, .right.inactive, .last.inactive {
		text-decoration: unset;
		color: unset;
		cursor: unset;
	}
	</style>
	<div style="flex: 7">
		<div class="banner idle"><span>No batch job running</span><button>Start Job</button></div>
		<h2>Previous imports</h2>
		<div id="job_container">

<?php
		foreach(new DirectoryIterator("jobs") as $fileIt)
		{
			if($fileIt->isDot() || $fileIt->isDir())
				continue;
			if($fileIt->getFilename() == "pid")
				continue;

			$count++;
			$fname = $fileIt->getFilename();
			$ymd = substr($fname, 0, 10);
			$hms = str_replace("_", ":", substr($fname, 11, 8));
?>
			<div class="job-record collapsable closed" data-file="<?php echo $fname ?>" data-getflag="0">
				<div class="job-info">Date: <?php echo $ymd ?> | Time: <?php echo $hms ?> | Size: <?php echo human_filesize("jobs/$fname") ?></div>
				<div class="job-details">
					<strong class="ecount">Errors:</strong>
					<div class="job-errors">
						<div class="container"></div>
						<div class="error-pages" data-page="1">
							<span class="first inactive">&lt;&lt;</span>
							<span class="left inactive">&lt;</span>
							<span class="currentpage">1</span>
							<span class="right inactive">&gt;</span>
							<span class="last inactive">&gt;&gt;</span>
						</div>
					</div>
					<strong class="icount">Imported images:</strong>
					<div class="job-imports">
						<div class="container"></div>
						<div class="import-pages" data-page="1">
							<span class="first inactive">&lt;&lt;</span>
							<span class="left inactive">&lt;</span>
							<span class="currentpage">1</span>
							<span class="right inactive">&gt;</span>
							<span class="last inactive">&gt;&gt;</span>
						</div>
					</div>
				</div>
			</div>
<?php
		}
		if($count == 0)
			echo "No import logs";
?>

		</div>
	</div>

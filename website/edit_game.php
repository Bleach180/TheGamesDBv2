<?php
require_once __DIR__ . "/include/ErrorPage.class.php";
if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_MISSING_PARAM_ERROR);
	$errorPage->print_die();
}
require_once __DIR__ . "/include/login.phpbb.class.php";
$_user = phpBBUser::getInstance();
if(!$_user->isLoggedIn())
{
	$errorPage = new ErrorPage();
	$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
	$errorPage->SetMSG(ErrorPage::$MSG_NOT_LOGGED_IN_EDIT_ERROR);
	$errorPage->print_die();
}
else
{
	if(!$_user->hasPermission('u_edit_games'))
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_NO_PERMISSION_TO_EDIT_ERROR);
		$errorPage->print_die();
	}
}

require_once __DIR__ . "/include/header.footer.class.php";
require_once __DIR__ . "/include/TGDBUtils.class.php";
require_once __DIR__ . "/../include/TGDB.API.php";
require_once __DIR__ . "/../include/CommonUtils.class.php";


if(isset($_REQUEST['id']) && !empty($_REQUEST['id']) && is_numeric($_REQUEST['id']))
{
	$options = array("release_date" => true, "overview" => true, "players" => true, "rating" => true, "ESRB" => true, "boxart" => true, "coop" => true,
		"genres" => true, "publishers" => true, "platform" => true, "youtube" => true);
	$API = TGDB::getInstance();
	$GenreList = $API->GetGenres();
	$ESRBRating = $API->GetESRBRating();
	$list = $API->GetGameByID($_REQUEST['id'], 0, 1, $options);

	if(empty($list))
	{
		$errorPage = new ErrorPage();
		$errorPage->SetHeader(ErrorPage::$HEADER_OOPS_ERROR);
		$errorPage->SetMSG(ErrorPage::$MSG_REMOVED_GAME_INVALID_PARAM_ERROR);
		$errorPage->print_die();
	}
	else
	{
		$Game = array_shift($list);
		$covers = $API->GetGameBoxartByID($_REQUEST['id'], 0, 9999, 'ALL');
		if(!empty($covers))
		{
			$Game->boxart = $covers[$_REQUEST['id']];
		}
	}
	$Platform = $API->GetPlatforms($Game->platform, array("icon" => true, "overview" => true, "developer" => true));
	if(isset($Platform[$Game->platform]))
	{
		$Platform = $Platform[$Game->platform];
	}
}

$devs_list = $API->GetDevsList();
$game_devs = $API->GetDevsListByIDs($Game->developers);

$pubs_list = $API->GetPubsList();
$game_pubs = $API->GetPubsListByIDs($Game->publishers);

$fanarts = TGDBUtils::GetAllCovers($Game, 'fanart', '');
$screenshots = TGDBUtils::GetAllCovers($Game, 'screenshot', '');
$banners = TGDBUtils::GetAllCovers($Game, 'series', '');
$is_graphics_empty = empty($fanarts) && empty($screenshots) && empty($banners);

$box_cover = new \stdClass();
$box_cover->front = TGDBUtils::GetAllCovers($Game, 'boxart', 'front');
if(!empty($box_cover->front))
{
	$box_cover->front = $box_cover->front[0];
}
$box_cover->back = TGDBUtils::GetAllCovers($Game, 'boxart', 'back');
if(!empty($box_cover->back))
{
	$box_cover->back = $box_cover->back[0];
}

$Header = new HEADER();
$Header->setTitle("TGDB - Browse - Game - $Game->game_title");
$Header->appendRawHeader(function() { global $Game, $_user, $game_devs, $devs_list, $game_pubs, $pubs_list; ?>

	<meta property="og:title" content="<?= $Game->game_title; ?>" />
	<meta property="og:type" content="article" />
	<meta property="og:image" content="<?= !empty($box_cover->front) ? $box_cover->front->thumbnail : "" ?>" />
	<meta property="og:description" content="<?= htmlspecialchars($Game->overview); ?>" />

	<link href="/css/select-pure.css" rel="stylesheet">
	<link href="/css/social-btn.css" rel="stylesheet">
	<link href="/css/fontawesome.5.0.10.css" rel="stylesheet">
	<link href="/css/fa-brands.5.0.10.css" rel="stylesheet">
	<link href="/css/jquery.fancybox.min.3.3.5.css" rel="stylesheet">

	<script type="text/javascript" defer src="/js/brands.5.0.10.js" crossorigin="anonymous"></script>
	<script type="text/javascript" defer src="/js/fontawesome.5.0.10.js" crossorigin="anonymous"></script>

	<script type="text/javascript" src="/js/jquery.fancybox.3.3.5.js"></script>
	<script type="text/javascript" src="/js/fancybox.config.js"></script>
	<script type="text/javascript" src="https://unpkg.com/select-pure@latest/dist/bundle.min.js"></script>

	<script type="text/javascript">
		function isJSON(json)
		{
			try
			{
				return (JSON.parse(json) && !!json);
			}
			catch (e)
			{
				return false;
			}
		}
		$(document).ready(function()
		{
			const multi_devs_selection = [
				<?php foreach($devs_list as $dev) : ?> { label: "<?= $dev->name ?>", value: "<?= $dev->id ?>" },<?php endforeach; ?>
			];
			const multi_devs_selected = [
				<?php foreach($game_devs as $dev) : ?> "<?= $dev->id ?>", <?php endforeach; ?>
				];
			multi_devs = new SelectPure('#devs_list', {
				options: multi_devs_selection,
				value: multi_devs_selected,
				autocomplete: true,
				multiple: true,
				icon: "fas fa-times",
			});

			const multi_pubs_selection = [
				<?php foreach($pubs_list as $pub) : ?> { label: "<?= $pub->name ?>", value: "<?= $pub->id ?>" },<?php endforeach; ?>
			];
			const multi_pubs_selected = [
				<?php foreach($game_pubs as $pub) : ?> "<?= $pub->id ?>", <?php endforeach; ?>
				];
			multi_pubs = new SelectPure('#pubs_list', {
				options: multi_pubs_selection,
				value: multi_pubs_selected,
				autocomplete: true,
				multiple: true,
				icon: "fas fa-times",
			});

			fancyboxOpts.share.descr = function(instance, item)
			{
				return "<?= $Game->game_title ?>";
			};
			$('[data-fancybox]').fancybox(fancyboxOpts);

			$("#game_edit").submit(function(e)
			{

				var url = "./actions/edit_game.php"; // the script where you handle the form input.

				$.ajax({
					type: "POST",
					url: url,
					data: $("#game_edit").serialize() + "&developers%5B%5D=" + multi_devs._config.value.join("&developers%5B%5D=") + "&publishers%5B%5D=" + multi_pubs._config.value.join("&publishers%5B%5D="),
					success: function(data)
					{
						if(isJSON(data))
						{
							var obj = JSON.parse(data);
							if(obj.code == -2)
							{
								// TODO: prompt user to no pub/dev
								// then allow user to procced
							}
							else
							{
								alert(data);
							}
							return;
						}
						else
						{
							alert("Error: Something Unexpected Happened.")
							return;
						}
					}
				});

				e.preventDefault(); // avoid to execute the actual submit of the form.
			});

			$("#game_delete").click(function(e)
			{
				<?php if($_user->hasPermission('m_delete_games')): ?>
				if (confirm('Deleting game record is irreversible, are you sure you want to continue?'))
				{
					var url = "./actions/delete_game.php";
					$.ajax({
						type: "POST",
						url: url,
						data: { game_id: <?= $Game->id ?> },
						success: function(data)
						{
							if(isJSON(data))
							{
								var obj = JSON.parse(data);
								alert(data)
								return;
							}
							else
							{
								alert("Error: Something Unexpected Happened.")
								return;
							}
						}
					});
					e.preventDefault();
				}
				<?php else :?>
				alert("you dont have permission to delete the game, please report it instead, thanks.");
				<?php endif ?>
			});
		});
	</script>
	<style type="text/css">
		.cover
		{
			width: 100%;
			position: relative;
		}
		
		@media screen and (min-width: 800px)
		{
			.cover-offset
			{
				margin-top: <?= isset($_REQUEST['test']) ? "-250px" : "-170px" ?>;
			}
			.fanart-banner
			{
				max-height: 100%;
				height: <?= isset($_REQUEST['test']) ? "200px" : "325px" ?>;
				overflow: hidden;
				text-align: center;
			}
		}

		@media screen and (max-width: 800px)
		{
			.cover-offset
			{
				margin-top: 0;
			}

			.fanart-banner
			{
				max-height: 100%;
				height: 175px;
				overflow: hidden;
				text-align: center;
			}
		}
		.margin5px
		{
			margin: 5px;
		}

		input[type="checkbox"]
		{
			position: absolute;
		}
		input[type="checkbox"] ~ label
		{
			text-overflow: ellipsis;
			display: inline-block;
			overflow: hidden;
			width: 90%;
			white-space: nowrap;
			vertical-align: middle;
			margin-left: 1.2rem;
		}
	</style>

	<link href="/css/fine_uploader.5.16.2/fine-uploader-new.css" rel="stylesheet">

	<!-- Fine Uploader jQuery JS file
	====================================================================== -->
	<script src="/js/fine_uploader.5.16.2/jquery.fine-uploader.js"></script>

	<!-- Fine Uploader Thumbnails template w/ customization
	====================================================================== -->
	<script type="text/template" id="qq-template-manual-trigger">
		<div class="qq-uploader-selector qq-uploader" qq-drop-area-text="Drop files here">
			<div class="qq-upload-drop-area-selector qq-upload-drop-area" qq-hide-dropzone>
				<span class="qq-upload-drop-area-text-selector"></span>
			</div>
			<div class="buttons">
				<div class="qq-upload-button-selector qq-upload-button">
					<div>Select files</div>
				</div>
				<button type="button" id="trigger-upload" class="btn btn-primary">
					<i class="icon-upload icon-white"></i> Upload
				</button>
			</div>
			<div class="qq-total-progress-bar-container-selector qq-total-progress-bar-container">
				<div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-total-progress-bar-selector qq-progress-bar qq-total-progress-bar"></div>
			</div>
			<span class="qq-drop-processing-selector qq-drop-processing">
				<span>Processing dropped files...</span>
				<span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>
			</span>
			<ul class="qq-upload-list-selector qq-upload-list" aria-live="polite" aria-relevant="additions removals">
				<li>
					<div class="qq-progress-bar-container-selector">
						<div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-progress-bar-selector qq-progress-bar"></div>
					</div>
					<span class="qq-upload-spinner-selector qq-upload-spinner"></span>
					<img class="qq-thumbnail-selector" qq-max-size="100" qq-server-scale>
					<span class="qq-upload-file-selector qq-upload-file"></span>
					<input class="qq-edit-filename-selector qq-edit-filename" tabindex="0" type="text">
					<span class="qq-upload-size-selector qq-upload-size"></span>
					<button type="button" class="qq-btn qq-upload-cancel-selector qq-upload-cancel">Cancel</button>
					<button type="button" class="qq-btn qq-upload-retry-selector qq-upload-retry">Retry</button>
					<button type="button" class="qq-btn qq-upload-delete-selector qq-upload-delete">Delete</button>
					<span role="status" class="qq-upload-status-text-selector qq-upload-status-text"></span>
				</li>
			</ul>

			<dialog class="qq-alert-dialog-selector">
				<div class="qq-dialog-message-selector"></div>
				<div class="qq-dialog-buttons">
					<button type="button" class="qq-cancel-button-selector">Close</button>
				</div>
			</dialog>

			<dialog class="qq-confirm-dialog-selector">
				<div class="qq-dialog-message-selector"></div>
				<div class="qq-dialog-buttons">
					<button type="button" class="qq-cancel-button-selector">No</button>
					<button type="button" class="qq-ok-button-selector">Yes</button>
				</div>
			</dialog>

			<dialog class="qq-prompt-dialog-selector">
				<div class="qq-dialog-message-selector"></div>
				<input type="text">
				<div class="qq-dialog-buttons">
					<button type="button" class="qq-cancel-button-selector">Cancel</button>
					<button type="button" class="qq-ok-button-selector">Ok</button>
				</div>
			</dialog>
		</div>
	</script>
	<script>
		var is_uploading = false;

		$(document).ready(function()
		{
			var fineuploader_config =
			{
				template: 'qq-template-manual-trigger',
				request:
				{
					endpoint: '/actions/uploads.php',
				},
				thumbnails:
				{
					placeholders:
					{
						waitingPath: '/css/fine_uploader.5.16.2/placeholders/waiting-generic.png',
						notAvailablePath: '/css/fine_uploader.5.16.2/placeholders/not_available-generic.png'
					}
				},
				validation:
				{
					itemLimit: 5,
					acceptFiles: 'image/*',
					allowedExtensions: ['jpe', 'jpg', 'jpeg', 'gif', 'png', 'bmp']
				},
				callbacks:
				{
					onAllComplete: function(succeeded, failed)
					{
						is_uploading = false;
					},
					onUpload : function(id, name)
					{
						is_uploading = true;
						this.setParams(
						{
							game_id : <?= $Game->id ?>,
							type : upload_type,
							subtype : upload_subtype,
						});
					}
				},
				autoUpload: false
			};
			$('#fine-uploader-manual-trigger').fineUploader(fineuploader_config);

			var upload_type = "";
			var upload_subtype = "";
			$('#trigger-upload').click(function()
			{
				$('#fine-uploader-manual-trigger').fineUploader('uploadStoredFiles');
			});

			$('#UploadModal2').on('show.bs.modal', function(event)
			{
				var button = $(event.relatedTarget)
				upload_type = button.data('upload-type')
				upload_subtype = button.data('upload-subtype')

				var modal = $(this)
				modal.find('.modal-title').text('Uploading ' + upload_type + ' ' + upload_subtype)
			})
			// bootstrap doesnt handled nested modal, as such closing the top modal by clicking on the backdrop closes all modal,
			// only closes only 1 backdrop to work around this we have to trigger another hiding
			$('#UploadModal').on('hidden.bs.modal', function(e)
			{
				$('#UploadModal2').modal('hide');
			});
			$('#UploadModal2Button').click(function()
			{
				if(is_uploading)
				{
					alert("Uploading is in progress");
				}
				else
				{
					$("#UploadModal2").modal('hide');
					$('#fine-uploader-manual-trigger').fineUploader('clearStoredFiles');
				}
			});
		});
	</script>
<?php });?>
<?= $Header->print(); ?>

	<form id="game_edit" class="container-fluid">
		<input name="game_id" type="hidden" value="<?= $Game->id ?>"/>
		<div class="row" style="padding-bottom: 10px;">
			<div class="col">
				<div id="cover" class="view-width fanart-banner">
				<?php if(!empty($cover = $fanarts) || !empty($cover = $screenshots)): ?>
					<img class="cover cover-offset" src="<?= $cover[0]->medium ?>"/>
				<?php else: ?>
					<img class="cover" src="<?= CommonUtils::$BOXART_BASE_URL ?>/placeholder_game_banner.png"/>
				<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="row">

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<?php if(!empty($box_cover->front)) : ?>
							<a class="fancybox-thumb" data-fancybox="cover" data-caption="Front Cover" href="<?= $box_cover->front->original ?>">
								<img class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
								<?php if(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" style="display:none;" data-fancybox="cover" data-caption="Back Cover"
								href="<?= $box_cover->back->original ?>" data-thumb="<?= $box_cover->back->thumbnail ?>"/>
							</a>
								<?php endif; ?>
								
							<?php elseif(!empty($box_cover->back)): ?>
							<a class="fancybox-thumb" data-fancybox="cover" data-caption="Back Cover" href="<?= $box_cover->front->original ?>">
								<img class="card-img-top" src="<?= $box_cover->front->thumbnail ?>"/>
							</a>
							<?php else: ?>
								<img class="card-img-top" src="<?= TGDBUtils::GetPlaceholderImage($Game->game_title, 'boxart'); ?>"/>
							<?php endif; ?>
							</a>
							<div class="card-body">
								<p>Platform: <a href="/platform.php?id=<?= $Platform->id?>"><?= $Platform->name; ?></a></p>
								<p>Developer: <input type="text" name="developer" value="<?= $Game->developer; ?>"/></p>
								<p>Publisher: <input type="text" name="publisher" value="<?= $Game->publisher; ?>"/></p>
								<p>ReleaseDate*:<br/> <input id="date" name="release_date" type="date" value="<?= $Game->release_date ;?>"></p>
								<p>Players:
									<select name="players">
									<?php for($x = 0; $x < 17; ++$x) : ?>
										<option value="<?= $x ?>" <?= ($Game->players == $x) ? "selected" : "" ?>><?= $x ?></option>
									<?php endfor; ?>
									</select>
								</p>
								<p>Co-op:
									<select name="coop">
										<option value="Yes" <?= ($Game->coop == "Yes") ? "selected" : "" ?>>Yes</option>
										<option value="No" <?= ($Game->coop == "No") ? "selected" : "" ?>>No</option>
									</select>
								</p>
								<p>* : safari doesnt support calender input yet, so please keep date format to (yyyy-mm-dd)</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-12 col-md-9 col-lg-8">
				<div class="row" style="padding-bottom:10px">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<h1><input style="width:100%" name="game_title" value="<?= $Game->game_title?>"/></h1>
							</div>
							<div class="card-body">
								<p>
									<textarea name="overview" rows=10 style="width:100%" placeholder="No overview is currently available for this title, please feel free to add one."><?= !empty($Game->overview) ?
									$Game->overview : "";?></textarea>
								</p>
								<p>YouTube Trailer: <input name="youtube" value="<?= $Game->youtube?>"/></p>
							</div>
						</div>
					</div>
				</div>

				<?php if (true) : ?>
				<div class="row" style="padding-bottom:10px;">
					<div class="col">
						<div class="card border-primary">
							<h3 class="card-header">
								Other Graphic(s)
							</h3>
							<div class="card-body">
								<div class="row justify-content-center">
									<?php if(!empty($cover = array_shift($fanarts))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="fanarts" data-caption="Fanart" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>" alt=""/>
											<img src="/images/ribbonFanarts.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($fanarts)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="fanarts" data-caption="Fanart"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if(!empty($cover = array_shift($screenshots))) : ?>
									<div class="col-12 col-sm-6" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="screenshots" data-caption="Screenshot" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->cropped_center_thumb ?>"/>
											<img src="/images/ribbonScreens.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($screenshots)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="screenshots" data-caption="Screenshot"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>

									<?php if(!empty($cover = array_shift($banners))) : ?>
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<a class="fancybox-thumb" data-fancybox="banners" data-caption="Banner" href="<?= $cover->original ?>">
											<img class="rounded img-thumbnail img-fluid" src="<?= $cover->thumbnail ?>"/>
											<img src="/images/ribbonBanners.png" style="position: absolute; left: 15px; top: 0; height: 80%; z-index: 10"/>
										</a>
										<?php while($cover = array_shift($banners)) : ?>
											<a class="fancybox-thumb" style="display:none" data-fancybox="banners" data-caption="Banner"
												href="<?= $cover->original ?>" data-thumb="<?= $cover->thumbnail ?>"></a>
										<?php endwhile; ?>
									</div>
									<?php endif; ?>
									<?php if($is_graphics_empty) : ?>
									<div class="col-12" style="margin-bottom:10px; overflow:hidden;">
										<h5>No fanarts/screenshots/banners found, be the 1st to add them.</h5>
									</div>
									<?php endif; ?>
								</div>
							</div>
							
							<div class="card-footer text-right">
								<button type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-target="#UploadModal">Upload Images</button>

								<!-- Modal -->
								<div class="modal fade" id="UploadModal" tabindex="-1" role="dialog" aria-labelledby="UploadModalLabel" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="UploadModalLabel">Upload Images</h5>
												<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
												</button>
											</div>
											<div class="modal-body">
												<div class="container-fluid">
													<div class="row justify-content-center">
														<button type="button" data-upload-type="boxart" data-upload-subtype="front" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Front-Cover</button>
														<button type="button" data-upload-type="boxart" data-upload-subtype="back" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Back-Cover</button>
														<button type="button" data-upload-type="fanart" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Fanart</button>
														<button type="button" data-upload-type="screenshot" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Sceenshot</button>
														<button type="button" data-upload-type="series" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload Banners</button>
														<button type="button" data-upload-type="clearlogo" data-upload-subtype="" data-toggle="modal"
															  data-backdrop="static" data-keyboard="false" data-target="#UploadModal2" class="btn btn-primary margin5px col-4">Upload ClearLogo</button>
													</div>
													<div class="modal fade" id="UploadModal2" tabindex="-1" role="dialog" aria-labelledby="UploadModal2Label"
														aria-hidden="true">
														<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
															<div class="modal-content">
																<div class="modal-header">
																	<h5 class="modal-title" id="UploadModal2Label">Upload</h5>
																	<button id="UploadModal2Button" type="button" class="close" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																	</button>
																</div>
																<div class="modal-body">
																	<div id="fine-uploader-manual-trigger"></div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>

			<div class="col-12 col-md-3 col-lg-2" style="padding-bottom:10px; text-align: center;">
				<div class="row">
					<div class="col">
						<div class="card border-primary">
							<div class="card-header">
								<legend>Control Panel</legend>
							</div>

							<div class="card-body">
								<p><button type="submit" class="btn btn-primary btn-block">Save</button></p>
								<?php if($_user->hasPermission('m_delete_games')): ?>
								<p><button id="game_delete" type="button" class="btn btn-danger btn-block">Delete</button></p>
								<?php endif; ?>
								<p><a href="/game.php?id=<?= $Game->id ?>" class="btn btn-default btn-block">Back</a></p>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</form>

<?php FOOTER::print(); ?>

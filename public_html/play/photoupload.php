<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('xhtml/xhtml-image.class.php');
require_once('xhtml/forms/uploaded-file.class.php');
require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');
require_once('media/media-gallery-manager.class.php');
require_once('media/image-manager.class.php');

class CurrentPage extends Page
{
	private $saved;

	public function OnPostback()
	{
		# First, check that a gallery's been specified
		if (!isset($_POST['album']) or !is_numeric($_POST['album'])) die();

		# Next check that the current user is an admin, or the owner of the gallery
		if(!AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS))
		{
			$gallery_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());
			$gallery_manager->ReadById(array($_POST['album']));
			$gallery = $gallery_manager->GetFirst();
			unset($gallery_manager);
			if (AuthenticationManager::GetUser()->GetId() != $gallery->GetAddedBy()->GetId()) die();
		}


		$file = new UploadedFile($this->GetSettings(), 'Filedata');
		$saved_as = $file->Save();

		/*
		Because it's possible to upload a non-image as *.jpg, there may be a GD error when the
		code attempts to resize the image. In that case we want to delete the saved file, so
		save the filename where the new error handler can get it
		*/
		$this->saved = array($saved_as);
		set_error_handler(array($this, 'HandleGdError'));

		if ($saved_as)
		{
			$saved_as = str_replace($this->GetSettings()->GetFolder('ImagesServer'), '', $saved_as);
			$image = new XhtmlImage($this->GetSettings(), $saved_as);
			$image->SetOriginalUrl($saved_as);
			$image->SetIsNewUpload(true);

			# Since a validated file is an image, generate a thumbnail
			$saved_as = $file->GenerateImageWeb();
			if ($saved_as)
			{
				$this->saved[] = $saved_as;
				$saved_as = str_replace($this->GetSettings()->GetFolder('ImagesServer'), '', $saved_as);
				$image->SetUrl($saved_as);
			}

			$saved_as = $file->GenerateImageThumbnail();
			if ($saved_as)
			{
				$this->saved[] = $saved_as;
				$saved_as = str_replace($this->GetSettings()->GetFolder('ImagesServer'), '', $saved_as);
				$image->SetThumbnailUrl($saved_as);
			}

			# Save to album
			$gallery = new MediaGallery($this->GetSettings());
			$gallery->SetId($_POST['album']);
			$image->AddGallery($gallery);
			$manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
			$manager->Save($image);

            # Add or update the album in search results
            $gallery_manager = new MediaGalleryManager($this->GetSettings(), $this->GetDataConnection());
            $gallery_manager->ReadImagesByGalleryId(array($_POST['album']));
            $gallery = $gallery_manager->GetFirst();
                
            require_once("search/lucene-search.class.php");
            $search = new LuceneSearch();
            $search->DeleteDocumentById("photos" . $_POST['album']);
            $search->IndexGallery($gallery);
            $search->CommitChanges();
    
			# Since it's this script that's queueing files for deletion (see HandleGdError, below) see
			# whether there are any queued for deletion from earlier requests.
			$manager->DeleteQueuedImages();

			echo $image->GetThumbnailUrl();
			exit();
		}
	}

	/**
	 * Handle an error in the upload process (old-style, as thrown by GD functions or MySQL errors) by deleting the uploaded files
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param array $errcontext
	 */
	public function HandleGdError($errno, $errstr, $errfile, $errline, array $errcontext)
	{
		# Ignore minor errors
		if ($errno == E_STRICT || $errno == E_NOTICE) return;

		$manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
		foreach ($this->saved as $saved_file)
		{
			# Can't actually delete file yet (at least locally) due to permission error.
			# This has to be something to do with having a lock on the file as any other script,
			# including future calls to this one, can delete it.
			#
			# Queue the file for deletion instead.
			$manager->QueueImageForDeletion($saved_file);
		}

		# die() has to return a message here even though it's never displayed. Without the message the
		# behaviour of the SWFUpload on the calling script changes, with UploadComplete never called.
		die($errstr . 'There was a problem saving the photo. Please check the file on your computer and try again.');
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::AddImage(), false);
?>
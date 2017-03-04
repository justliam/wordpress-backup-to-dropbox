<?php

/**
 * Dropbox API base class
 * @author Ben Tadiar <ben@handcraftedbyben.co.uk>
 * @link https://github.com/benthedesigner/dropbox
 * @link https://www.dropbox.com/developers
 * @link https://status.dropbox.com Dropbox status
 * @package Dropbox
 */

class Dropbox_API
{
    // API Endpoints
    const API_URL     = 'https://api.dropbox.com/1/';
    const CONTENT_URL = 'https://api-content.dropbox.com/1/';

    /**
     * OAuth consumer object
     * @var null|OAuth\Consumer
     */
    private $OAuth;

    /**
     * The root level for file paths
     * Either `dropbox` or `sandbox` (preferred)
     * @var null|string
     */
    private $root;

    /**
     * Chunk size used for chunked uploads
     * @see \Dropbox_API::chunkedUpload()
     */
    private $chunkSize = 4194304;

    /**
     * Object to track uploads
     */
    private $tracker;

    /**
     * Set the OAuth consumer object
     * See 'General Notes' at the link below for information on access type
     * @link https://www.dropbox.com/developers/reference/api
     * @param OAuth\Consumer\ConsumerAbstract $OAuth
     * @param string                          $root  Dropbox app access type
     */
    public function __construct($OAuth, $root = 'sandbox')
    {
        $this->OAuth = $OAuth;
        $this->setRoot($root);
    }

    /**
    * Set the root level
    * @param mixed $root
    * @throws Exception
    * @return void
    */
    public function setRoot($root)
    {
        if ($root !== 'sandbox' && $root !== 'dropbox') {
            throw new Exception("Expected a root of either 'dropbox' or 'sandbox', got '$root'");
        } else {
            $this->root = $root;
        }
    }

     /**
    * Set the tracker
    * @param Tracker $tracker
    */
    public function setTracker($tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * Retrieves information about the user's account
     * @return object stdClass
     */
    public function accountInfo()
    {
        return $this->fetch('POST', self::API_URL, 'account/info');
    }

    /**
     * Uploads a physical file from disk
     * Dropbox impose a 150MB limit to files uploaded via the API. If the file
     * exceeds this limit or does not exist, an Exception will be thrown
     * @param  string      $file      Absolute path to the file to be uploaded
     * @param  string|bool $filename  The destination filename of the uploaded file
     * @param  string      $path      Path to upload the file to, relative to root
     * @param  boolean     $overwrite Should the file be overwritten? (Default: true)
     * @return object      stdClass
     */
    public function putFile($file, $filename = false, $path = '', $overwrite = true)
    {
        if (file_exists($file)) {
            if (filesize($file) <= 157286400) {
                $call = 'files/' . $this->root . '/' . $this->encodePath($path);
                // If no filename is provided we'll use the original filename
                $filename = (is_string($filename)) ? $filename : basename($file);
                $params = array(
                    'filename' => $filename,
                    'file' => '@' . str_replace('\\', '/', $file) . ';filename=' . $filename,
                    'overwrite' => (int) $overwrite,
                );

                return $this->fetch('POST', self::CONTENT_URL, $call, $params);

            }
            throw new Exception('File exceeds 150MB upload limit');
        }

        // Throw an Exception if the file does not exist
        throw new Exception('Local file ' . $file . ' does not exist');
    }

    /**
     * Uploads file data from a stream
     * Note: This function is experimental and requires further testing
     * @todo Add filesize check
     * @param  resource $stream    A readable stream created using fopen()
     * @param  string   $filename  The destination filename, including path
     * @param  boolean  $overwrite Should the file be overwritten? (Default: true)
     * @return array
     */
    public function putStream($stream, $filename, $overwrite = true)
    {
        $this->OAuth->setInFile($stream);
        $path = $this->encodePath($filename);
        $call = 'files_put/' . $this->root . '/' . $path;
        $params = array('overwrite' => (int) $overwrite);

        return $this->fetch('PUT', self::CONTENT_URL, $call, $params);
    }

    /**
     * Uploads large files to Dropbox in mulitple chunks
     * @param  string      $file      Absolute path to the file to be uploaded
     * @param  string|bool $filename  The destination filename of the uploaded file
     * @param  string      $path      Path to upload the file to, relative to root
     * @param  boolean     $overwrite Should the file be overwritten? (Default: true)
     * @return stdClass
     */
    public function chunkedUpload($file, $filename = false, $path = '', $overwrite = true, $offset = 0, $uploadID = null)
    {
        if (file_exists($file)) {
            if ($handle = @fopen($file, 'r')) {

                // Seek to the correct position on the file pointer
                fseek($handle, $offset);

                // Read from the file handle until EOF, uploading each chunk
                while ($data = fread($handle, $this->chunkSize)) {
                    // Open a temporary file handle and write a chunk of data to it
                    $chunkHandle = fopen('php://temp', 'rw');
                    fwrite($chunkHandle, $data);

                    // Set the file, request parameters and send the request
                    $this->OAuth->setInFile($chunkHandle);
                    $params = array('upload_id' => $uploadID, 'offset' => $offset);

                    try {
                        // Attempt to upload the current chunk
                        $response = $this->fetch('PUT', self::CONTENT_URL, 'chunked_upload', $params);
                    } catch (Exception $e) {
                        $response = $this->OAuth->getLastResponse();
                        if ($response['code'] != 400) {
                            // Re-throw the caught Exception
                            throw $e;
                        }
                    }

                    // On subsequent chunks, use the upload ID returned by the previous request
                    if (isset($response['body']->upload_id)) {
                        $uploadID = $response['body']->upload_id;
                    }

                    // Set the data offset
                    if (isset($response['body']->offset)) {
                        $offset = $response['body']->offset;
                    }

                    if ($this->tracker) {
                        $this->tracker->track_upload($file, $uploadID, $offset);
                    }

                    // Close the file handle for this chunk
                    fclose($chunkHandle);
                }

                // Complete the chunked upload
                $filename = (is_string($filename)) ? $filename : basename($file);
                $call = 'commit_chunked_upload/' . $this->root . '/' . $this->encodePath(rtrim($path, '/') . '/' . $filename);
                $params = array('overwrite' => (int) $overwrite, 'upload_id' => $uploadID);

                return $this->fetch('POST', self::CONTENT_URL, $call, $params);

            } else {
                throw new Exception('Could not open ' . $file . ' for reading');
            }
        }

        // Throw an Exception if the file does not exist
        throw new Exception('Local file ' . $file . ' does not exist');
    }

    /**
     * Downloads a file
     * Returns the base filename, raw file data and mime type returned by Fileinfo
     * @param  string $file     Path to file, relative to root, including path
     * @param  string $outFile  Filename to write the downloaded file to
     * @param  string $revision The revision of the file to retrieve
     * @return array
     */
    public function getFile($file, $outFile = false, $revision = null)
    {
        $handle = null;
        if ($outFile !== false) {
            // Create a file handle if $outFile is specified
            if (!$handle = fopen($outFile, 'w')) {
                throw new Exception("Unable to open file handle for $outFile");
            } else {
                $this->OAuth->setOutFile($handle);
            }
        }

        $file = $this->encodePath($file);
        $call = 'files/' . $this->root . '/' . $file;
        $params = array('rev' => $revision);
        $response = $this->fetch('GET', self::CONTENT_URL, $call, $params);

        // Close the file handle if one was opened
        if ($handle) fclose($handle);
        return array(
            'name' => ($outFile) ? $outFile : basename($file),
            'mime' => $this->getMimeType(($outFile) ? $outFile : $response['body'], $outFile),
            'meta' => json_decode($response['headers']['x-dropbox-metadata']),
            'data' => $response['body'],
        );
    }

    /**
     * Retrieves file and folder metadata
     * @param  string $path    The path to the file/folder, relative to root
     * @param  string $rev     Return metadata for a specific revision (Default: latest rev)
     * @param  int    $limit   Maximum number of listings to return
     * @param  string $hash    Metadata hash to compare against
     * @param  bool   $list    Return contents field with response
     * @param  bool   $deleted Include files/folders that have been deleted
     * @return object stdClass
     */
    public function metaData($path = null, $rev = null, $limit = 10000, $hash = false, $list = true, $deleted = false)
    {
        $call = 'metadata/' . $this->root . '/' . $this->encodePath($path);
        $params = array(
            'file_limit' => ($limit < 1) ? 1 : (($limit > 10000) ? 10000 : (int) $limit),
            'hash' => (is_string($hash)) ? $hash : 0,
            'list' => (int) $list,
            'include_deleted' => (int) $deleted,
            'rev' => (is_string($rev)) ? $rev : null,
        );

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Return "delta entries", intructing you how to update
     * your application state to match the server's state
     * Important: This method does not make changes to the application state
     * @param  null|string $cursor Used to keep track of your current state
     * @return array       Array of delta entries
     */
    public function delta($cursor = null)
    {
        $call = 'delta';
        $params = array('cursor' => $cursor);

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Obtains metadata for the previous revisions of a file
     * @param string Path to the file, relative to root
     * @param integer Number of revisions to return (1-1000)
     * @return array
     */
    public function revisions($file, $limit = 10)
    {
        $call = 'revisions/' . $this->root . '/' . $this->encodePath($file);
        $params = array(
            'rev_limit' => ($limit < 1) ? 1 : (($limit > 1000) ? 1000 : (int) $limit),
        );

        return $this->fetch('GET', self::API_URL, $call, $params);
    }

    /**
     * Restores a file path to a previous revision
     * @param  string $file     Path to the file, relative to root
     * @param  string $revision The revision of the file to restore
     * @return object stdClass
     */
    public function restore($file, $revision)
    {
        $call = 'restore/' . $this->root . '/' . $this->encodePath($file);
        $params = array('rev' => $revision);

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Returns metadata for all files and folders that match the search query
     * @param  mixed   $query   The search string. Must be at least 3 characters long
     * @param  string  $path    The path to the folder you want to search in
     * @param  integer $limit   Maximum number of results to return (1-1000)
     * @param  boolean $deleted Include deleted files/folders in the search
     * @return array
     */
    public function search($query, $path = '', $limit = 1000, $deleted = false)
    {
        $call = 'search/' . $this->root . '/' . $this->encodePath($path);
        $params = array(
            'query' => $query,
            'file_limit' => ($limit < 1) ? 1 : (($limit > 1000) ? 1000 : (int) $limit),
            'include_deleted' => (int) $deleted,
        );

        return $this->fetch('GET', self::API_URL, $call, $params);
    }

    /**
     * Creates and returns a shareable link to files or folders
     * The link returned is for a preview page from which the user an choose to
     * download the file if they wish. For direct download links, see media().
     * @param  string $path The path to the file/folder you want a sharable link to
     * @return object stdClass
     */
    public function shares($path, $shortUrl = true)
    {
        $call = 'shares/' . $this->root . '/' .$this->encodePath($path);
        $params = array('short_url' => ($shortUrl) ? 1 : 0);

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Returns a link directly to a file
     * @param  string $path The path to the media file you want a direct link to
     * @return object stdClass
     */
    public function media($path)
    {
        $call = 'media/' . $this->root . '/' . $this->encodePath($path);

        return $this->fetch('POST', self::API_URL, $call);
    }

    /**
     * Gets a thumbnail for an image
     * @param  string $file   The path to the image you wish to thumbnail
     * @param  string $format The thumbnail format, either JPEG or PNG
     * @param  string $size   The size of the thumbnail
     * @return array
     */
    public function thumbnails($file, $format = 'JPEG', $size = 'small')
    {
        $format = strtoupper($format);
        // If $format is not 'PNG', default to 'JPEG'
        if ($format != 'PNG') $format = 'JPEG';

        $size = strtolower($size);
        $sizes = array('s', 'm', 'l', 'xl', 'small', 'medium', 'large');
        // If $size is not valid, default to 'small'
        if (!in_array($size, $sizes)) $size = 'small';

        $call = 'thumbnails/' . $this->root . '/' . $this->encodePath($file);
        $params = array('format' => $format, 'size' => $size);
        $response = $this->fetch('GET', self::CONTENT_URL, $call, $params);

        return array(
            'name' => basename($file),
            'mime' => $this->getMimeType($response['body']),
            'meta' => json_decode($response['headers']['x-dropbox-metadata']),
            'data' => $response['body'],
        );
    }

    /**
     * Creates and returns a copy_ref to a file
     * This reference string can be used to copy that file to another user's
     * Dropbox by passing it in as the from_copy_ref parameter on /fileops/copy
     * @param $path File for which ref should be created, relative to root
     * @return array
     */
    public function copyRef($path)
    {
        $call = 'copy_ref/' . $this->root . '/' . $this->encodePath($path);

        return $this->fetch('GET', self::API_URL, $call);
    }

    /**
     * Copies a file or folder to a new location
     * @param  string      $from        File or folder to be copied, relative to root
     * @param  string      $to          Destination path, relative to root
     * @param  null|string $fromCopyRef Must be used instead of the from_path
     * @return object      stdClass
     */
    public function copy($from, $to, $fromCopyRef = null)
    {
        $call = 'fileops/copy';
        $params = array(
            'root' => $this->root,
            'from_path' => $this->normalisePath($from),
            'to_path' => $this->normalisePath($to),
        );

        if ($fromCopyRef) {
            $params['from_path'] = null;
            $params['from_copy_ref'] = $fromCopyRef;
        }

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Creates a folder
     * @param string New folder to create relative to root
     * @return object stdClass
     */
    public function create($path)
    {
        $call = 'fileops/create_folder';
        $params = array('root' => $this->root, 'path' => $this->normalisePath($path));

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Deletes a file or folder
     * @param  string $path The path to the file or folder to be deleted
     * @return object stdClass
     */
    public function delete($path)
    {
        $call = 'fileops/delete';
        $params = array('root' => $this->root, 'path' => $this->normalisePath($path));

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Moves a file or folder to a new location
     * @param  string $from File or folder to be moved, relative to root
     * @param  string $to   Destination path, relative to root
     * @return object stdClass
     */
    public function move($from, $to)
    {
        $call = 'fileops/move';
        $params = array(
                'root' => $this->root,
                'from_path' => $this->normalisePath($from),
                'to_path' => $this->normalisePath($to),
        );

        return $this->fetch('POST', self::API_URL, $call, $params);
    }

    /**
     * Intermediate fetch function
     * @param  string $method The HTTP method
     * @param  string $url    The API endpoint
     * @param  string $call   The API method to call
     * @param  array  $params Additional parameters
     * @return mixed
     */
    private function fetch($method, $url, $call, array $params = array())
    {
        // Make the API call via the consumer
        return $this->OAuth->fetch($method, $url, $call, $params);
    }

    /**
     * Set the chunk size for chunked uploads
     * If $chunkSize is empty, set to 4194304 bytes (4 MB)
     * @see \Dropbox\API\chunkedUpload()
     */
    public function setChunkSize($chunkSize = 4194304)
    {
        if (!is_int($chunkSize)) {
            throw new Exception('Expecting chunk size to be an integer, got ' . gettype($chunkSize));
        } elseif ($chunkSize > 157286400) {
            throw new Exception('Chunk size must not exceed 157286400 bytes, got ' . $chunkSize);
        } else {
            $this->chunkSize = $chunkSize;
        }
    }

    /**
     * Get the mime type of downloaded file
     * If the Fileinfo extension is not loaded, return false
     * @param  string         $data       File contents as a string or filename
     * @param  string         $isFilename Is $data a filename?
     * @return boolean|string Mime type and encoding of the file
     */
    private function getMimeType($data, $isFilename = false)
    {
        if (extension_loaded('fileinfo')) {
            $finfo = new finfo(FILEINFO_MIME);
            if ($isFilename !== false) {
                return $finfo->file($data);
            }

            return $finfo->buffer($data);
        }

        return false;
    }

    /**
     * Trim the path of forward slashes and replace
     * consecutive forward slashes with a single slash
     * then replace backslashes with forward slashes
     * @param  string $path The path to normalise
     * @return string
     */
    private function normalisePath($path)
    {
        return str_replace('\\', '/', preg_replace('#/+#', '/', trim($path, '/')));
    }

    /**
     * Encode the path, then replace encoded slashes
     * with literal forward slash characters
     * @param  string $path The path to encode
     * @return string
     */
    private function encodePath($path)
    {
        $path = $this->normalisePath($path);

        return str_replace('%2F', '/', rawurlencode($path));
    }
}

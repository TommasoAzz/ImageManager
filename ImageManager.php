<?php
/**
 * Class ImageManager
 * This class manages the uploading process of one or more image files to the web server.
 * It can also rename them during the uploading process and delete them.
 */
class ImageManager {
    private $targetFolder;
    private $targetFileName;
    private $targetFileExtension;
    private $tempFileName;

    /**
     * Constructor of ImageManager.
     * @param string $targetFolder root folder path in which files will be uploaded
     */
    public function __construct($targetFolder) {
        // constant values once initialized
        $this->targetFolder = $targetFolder;

        $this->targetFileName = "";
        $this->targetFileExtension = "";
        $this->tempFileName = "";
    }

    /**
     * This method gets the file to upload from $_FILES. The file must have one of this extensions: JPG, JPEG, PNG.
     * @param string $img_name_attr name attribute value in <input type="file" ..></file> on the form from which the image will be loaded
     * @param string $outFileName name to assign to the file once uploaded, without the file extension (if not set, the chosen name will be the uploaded file's one) (default: "")
     * @param int $fileIndex index in the array $_FILES (default: -1, use only when uploading multiple files)
     * @throws Exception if the upload file is not an image, if the uploaded file dimension is higher than the server's maximum and if the extension is not valid; if the file was not uploaded
     */
    public function setFile($img_name_attr, $outFileName = "", $fileIndex = -1) {
        // ensuring fields are empty
        $this->unsetFile();

        /**
         * If $fileIndex === -1, the user requested a single file upload, using:
         * <input type="file" ... />.
         * If $fileIndex >= 0, the user requested many file upload, using:
         * <input type="file" ... multiple />
         */

        // ensuring one or more files are uploaded
        if(!isset($_FILES[$img_name_attr]) || $_FILES[$img_name_attr]["error"] === 4 || $_FILES[$img_name_attr]["error"][0] === 4) {
            throw new Exception("No file was uploaded.");   
        }
        
        if($_FILES[$img_name_attr]["error"] === 1 || $_FILES[$img_name_attr]["error"][0] === 1) {
            throw new Exception("The uploaded file/s dimensions are higher than the server's maximum.");
        }
        
        // ensuring the file index is in bound
        if($fileIndex !== -1 && $fileIndex > $this->countFiles($img_name_attr)) {
            throw new Exception("Requested a file out of bounds (it is not available).");
        }

        // getting the file name
        $name = $fileIndex !== -1 ? $_FILES[$img_name_attr]["name"][$fileIndex] : $_FILES[$img_name_attr]["name"];
        
        // getting the file extension
        $this->targetFileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        // getting the output file name (if present)
        $this->targetFileName = $this->targetFolder . ($outFileName === "" ? $name : $outFileName . "." . $this->targetFileExtension);
        
        // getting the temporary directory managed by PHP
        $this->tempFileName = $fileIndex !== -1 ? $_FILES[$img_name_attr]["tmp_name"][$fileIndex] : $_FILES[$img_name_attr]["tmp_name"];
        
        // verifying the image extension
        if ($this->targetFileExtension != "jpg" &&
            $this->targetFileExtension != "png" &&
            $this->targetFileExtension != "jpeg"
        ) {
            throw new Exception("The uploaded file has an incorrect file extension.");
        }
        
        // verifying that the uploaded file is actually an image file (veyfing its metadata)
        if (getimagesize($this->tempFileName) === false) {
            throw new Exception("The uploaded file is not an image.");
        }
    }

    /**
     * Returning the file name (with file extension) to upload or already uploaded.
     * @return string file name (with file extension) to upload or already uploaded
     * @throws Exception if file is not already set
     */
    public function fileName(): string {
        $this->verifyFilePresence();

        return pathinfo($this->targetFileName, PATHINFO_BASENAME);
    }

    /**
     * Returning the file extension of the file to upload or already uploaded.
     * @return string file extension of the file to upload or already uploaded
     * @throws Exception if file is not already set
     */
    public function fileExtension(): string {
        $this->verifyFilePresence();

        return $this->targetFileExtension;
    }

    /**
     * Moving the uploaded file in the requested folder during the creation of the object.
     * @return bool TRUE if file was moved, FALSE otherwise
     * @throws Exception if file is not already set
     */
    public function saveFile(): bool {
        $this->verifyFilePresence();
        return move_uploaded_file($this->tempFileName, $this->targetFileName);
    }

    /**
     * Returning the uploaded file number. Method can be called if $fileIndex was not -1
     * @param string $img_name_attr name attribute value in <input type="file" ..></file> on the form from which the image will be loaded
     * nel form da cui si sta recuperando l'immagine.
     */
    public function countFiles($img_name_attr): int {
        return count($_FILES[$img_name_attr]["name"]);
    }

    /**
     * Removes a file given its path.
     * @param string $fileName file name (with path)
     * @return bool TRUE if file was removed, FALSE otherwise
     */
    public static function deleteFile($fileName): bool {
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return ($fileExtension === "jpg" || $fileExtension === "png" || $fileExtension === "jpeg") ? unlink($fileName) : FALSE;
    }

    /**
     * Verifying if file is set.
     * @throws Exception if file is not set.
     */
    private function verifyFilePresence() {
        if (strlen($this->targetFileExtension) === 0 || strlen($this->targetFileName) === 0) {
            throw new Exception("No file has been set.");
        }
    }

    /**
     * Unsets data fields.
     */
    private function unsetFile() {
        $this->targetFileName = "";
        $this->targetFileExtension = "";
        $this->tempFileName = "";
    }
}

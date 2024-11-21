<?php

namespace App\Service;

use \Exception;

class PersistMetaData
{
    private $exif;
    private $metadados = [];
    private $filter = [
        'Length', 'Width', 'Height', 'Resolution', 'Meter', 'Milimeter',
        'Seconds', 'Date', 'Subseconds', 'ImageWidth', 'ImageLength', 'BitsPerSample',
        'Compression', 'PhotometricInterpretation', 'ImageDescription',
        'Make', 'Model', 'StripOffsets', 'Orientation', 'SamplesPerPixel',
        'RowsPerStrip', 'StripByteCounts', 'XResolution', 'YResolution', 'PlanarConfiguration',
        'ResolutionUnit', 'TransferFunction', 'Software', 'DateTime', 'Artist', 'WhitePoint', 'PrimaryChromaticities',
        'JPEGInterchangeFormat', 'JPEGInterchangeFormatLength', 'YCbCrCoefficients', 'YCbCrSubSampling', 'YCbCrPositioning',
        'ReferenceBlackWhite', 'Copyright', 'ExposureTime', 'FNumber', 'ExposureProgram', 'SpectralSensitivity', 'ISOSpeedRatings',
        'OECF', 'ExifVersion', 'DateTimeOriginal', 'DateTimeDigitized', 'ComponentsConfiguration', 'CompressedBitsPerPixel', 'ShutterSpeedValue',
        'ApertureValue', 'BrightnessValue', 'ExposureBiasValue', 'MaxApertureValue', 'SubjectDistance', 'MeteringMode', 'LightSource',
        'Flash', 'FocalLength', 'SubjectArea', 'MakerNote', 'UserComment', 'SubSecTime', 'SubSecTimeOriginal', 'SubSecTimeDigitized',
        'FlashpixVersion', 'ColorSpace', 'PixelXDimension', 'PixelYDimension', 'RelatedSoundFile', 'FlashEnergy', 'SpatialFrequencyResponse', 
        'FocalPlaneXResolution', 'FocalPlaneYResolution', 'FocalPlaneResolutionUnit', 'SubjectLocation', 'ExposureIndex',
        'SensingMethod', 'FileSource', 'SceneType', 'CFAPattern', 'CustomRendered', 'ExposureMode', 'WhiteBalance', 'DigitalZoomRatio', 
        'FocalLengthIn35mmFilm', 'SceneCaptureType', 'GainControl', 'Contrast', 'Saturation', 'Sharpness', 'DeviceSettingDescription', 
        'SubjectDistanceRange', 'ImageUniqueID', 'GPSVersionID', 'GPSLatitudeRef', 'GPSLatitude', 'GPSLongitudeRef', 'GPSLongitude', 'GPSAltitudeRef',
        'GPSAltitude', 'GPSTimeStamp', 'GPSSatellites', 'GPSStatus', 'GPSMeasureMode', 'GPSDOP', 'GPSSpeedRef', 'GPSSpeed', 'GPSTrackRef', 'GPSTrack',
        'GPSImgDirectionRef', 'GPSImgDirection', 'GPSMapDatum', 'GPSDestLatitudeRef', 'GPSDestLatitude', 'GPSDestLongitudeRef', 'GPSDestLongitude',
        'GPSDestBearingRef', 'GPSDestBearing', 'GPSDestDistanceRef', 'GPSDestDistance', 'GPSProcessingMethod', 'GPSAreaInformation', 'GPSDateStamp',
        'GPSDifferential', 'InteroperabilityIndex', 'InteroperabilityVersion', 'RelatedImageFileFormat', 'RelatedImageWidth', 'RelatedImageLength',
    ];
    
    public function setExif(string $path): self
    {
        $this->exif = exif_read_data($path, 'IFD0, EXIF', true, false);
        $this->fillVector();
        
        return $this;
    }

    public function getExif(): array
    {
        return $this->metadados;
    }

    public function hasExif(): bool
    {
        if ($this->exif) {
            return true;
        }

        return false;
    }

    public function hasExifTag(): bool
    {
        if ($this->hasExif() && array_key_exists('EXIF', $this->exif)) {
            return true;
        }

        return false;
    }

    public function hasIfdTag(): bool
    {
        if ($this->hasExif() && array_key_exists('IFD0', $this->exif)) {
            return true;
        }

        return false;
    }

    public function fillVector(): self
    {
        if ($this->hasExifTag()) {
            foreach ($this->exif['EXIF'] as $key => $value) {
                if (in_array($key, $this->filter)) {
                    $this->metadados[] = [
                        'nome' => $key,
                        'valor' => $value,
                    ];
                }
            }
        }

        if ($this->hasIfdTag()) {
            foreach ($this->exif['IFD0'] as $key => $value) {
                if (in_array($key, $this->filter)) {
                    $this->metadados[] = [
                        'nome' => $key,
                        'valor' => $value,
                    ];
                }
            }
        }

        return $this;
    }
}
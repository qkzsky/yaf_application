<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MP4;

class MP4Helper {
    const ATOM_PREAMBLE_SIZE    = 8;
    const COPY_BUFFER_SIZE      = 1024;
    
    const ATOM_TYPE_FREE    = 'free';
    const ATOM_TYPE_JUNK    = 'junk';
    const ATOM_TYPE_MDAT    = 'mdat';
    const ATOM_TYPE_MOOV    = 'moov';
    const ATOM_TYPE_PNOT    = 'pnot';
    const ATOM_TYPE_SKIP    = 'skip';
    const ATOM_TYPE_WIDE    = 'wide';
    const ATOM_TYPE_PICT    = 'PICT';
    const ATOM_TYPE_FTYP    = 'ftyp';
    const ATOM_TYPE_UUID    = 'uuid';
    const ATOM_TYPE_CMOV    = 'cmov';
    const ATOM_TYPE_STCO    = 'stco';
    const ATOM_TYPE_C064    = 'co64';
    
    const RETVAL_CONVERT    = 1;
    const RETVAL_NOT_NEED_CONVERT   = 2;
    
    /**
     * 最后一条错误消息
     *
     * @var string
     */
    private static $_lastError  = null;

    /**
     * 
     * @return string
     */
    public  static function getLastError() {
        return self::$_lastError;
    }

    public static function fastStart($srcPath, $destPath) {
        $srcfp  = fopen($srcPath, 'rb');
        if (!$srcfp) {
            self::$_lastError   = "Src Path {$srcPath} Cann't read or not exists";
            return false;
        }
        $atom   = self::detectAtom($srcfp);
        if ($atom === false) {
            fclose($srcfp);
            return false;
        }
        if ($atom['moovOffset'] === 0 || $atom['moovSize'] === 0) {
            self::$_lastError   = "don't found moov atom in file";
            fclose($srcfp);
            return false;
        }
        if ($atom['moovOffset'] < $atom['mdatOffset']) {
            fclose($srcfp);
            return self::RETVAL_NOT_NEED_CONVERT;   //视频无需转换
        }
        fseek($srcfp, $atom['moovOffset'], SEEK_SET);
        $lastOffset = ftell($srcfp);
        $moovAtom   = fread($srcfp, $atom['moovSize']);
        if ($moovAtom === false) {
            self::$_lastError   = "Read moov atom failed";
            return false;
        }
        if (self::ATOM_TYPE_CMOV === substr($moovAtom, 12, 4)) {
            self::$_lastError   = "this utility does not support compressed moov atoms yet";
            return false;
        }
        
        /* crawl through the moov chunk in search of stco or co64 atoms */
        for ($i = 4; $i < $atom['moovSize'] - 4; $i ++) {
            $atomType   = substr($moovAtom, $i, 4);
            if ($atomType === self::ATOM_TYPE_STCO) {
                $atomSize   = self::bigEndian32(substr($moovAtom, $i - 4, 4));
                if ($i + $atomSize - 4 > $atom['moovSize']) {
                    self::$_lastError   = "Bad stco atom size";
                    return false;
                }
                $offsetCount    = self::bigEndian32(substr($moovAtom, $i + 8, 4));
                for ($j = 0; $j < $offsetCount; $j ++) {
                    $currentOffset  = self::bigEndian32(substr($moovAtom, $i + 12 + $j * 4, 4));
                    $currentOffset  += $atom['moovSize'];
                    $moovAtom[$i + 12 + $j * 4 + 0] = chr(($currentOffset >> 24) & 0xFF);
                    $moovAtom[$i + 12 + $j * 4 + 1] = chr(($currentOffset >> 16) & 0xFF);
                    $moovAtom[$i + 12 + $j * 4 + 2] = chr(($currentOffset >> 8) & 0xFF);
                    $moovAtom[$i + 12 + $j * 4 + 3] = chr($currentOffset & 0xFF);
                }
                $i  += $atomSize - 4;
            } else if ($atomType === self::ATOM_TYPE_C064) {
                $atomSize   = self::bigEndian32($moovAtom, substr($i - 4, 4));
                if ($i + $atomSize - 4 > $atom['moovSize']) {
                    self::$_lastError   = "Bad co64 atom size";
                    return false;
                }
                $offsetCount    = self::bigEndian32($moovAtom, $i + 8, 4);
                for ($j = 0; $j < $offsetCount; $j ++) {
                    $currentOffset  = self::bigEndian64($moovAtom, $i + 12 + $j * 8, 8);
                    $currentOffset  += $atom['moovSize'];
                    $moovAtom[$i + 12 + $j * 8 + 0] = chr(($currentOffset >> 56) & 0xFF);
                    $moovAtom[$i + 12 + $j * 8 + 1] = chr(($currentOffset >> 48) & 0xFF);
                    $moovAtom[$i + 12 + $j * 8 + 2] = chr(($currentOffset >> 40) & 0xFF);
                    $moovAtom[$i + 12 + $j * 8 + 3] = chr(($currentOffset >> 32) & 0xFF);
                    $moovAtom[$i + 12 + $j * 8 + 4] = chr(($currentOffset >> 24) & 0xFF);
                    $moovAtom[$i + 12 + $j * 8 + 5] = chr(($currentOffset >> 16) & 0xFF);
                    $moovAtom[$i + 12 + $j * 8 + 6] = chr(($currentOffset >> 8) & 0xFF);
                    $moovAtom[$i + 12 + $j * 8 + 7] = chr($currentOffset & 0xFF);
                }
                $i  += $atomSize - 4;
            }
        }
        
        if ($atom['startOffset'] > 0) { /* seek after ftyp atom */
            fseek($srcfp, $atom['startOffset'], SEEK_SET);
            $lastOffset -= $atom['startOffset'];
        } else {
            rewind($srcfp);
        }
        
        $outfp  = fopen($destPath, 'wb');
        if (!$outfp) {
            self::$_lastError   = "Open dest file {$destPath} failed";
            return false;
        }
        
        /* dump the same ftyp atom */
        if ($atom['ftypSize'] > 0) {
            if (false === fwrite($outfp, $atom['atomFtyp'])) {
                self::$_lastError   = 'Write ftyp atom failed to destfile failed';
                return false;
            }
        }
        /* dump the new moov atom */
        if (false === fwrite($outfp, $moovAtom)) {
            self::$_lastError   = 'Write moov data to destfile failed';
            return false;
        }
        /* copy the remainder of the infile, from offset 0 -> last_offset - 1 */
        while ($lastOffset > 0) {
            $bufferSize = min([self::COPY_BUFFER_SIZE, $lastOffset]);
            $buffer     = fread($srcfp, $bufferSize);
            if ($buffer === false) {
                self::$_lastError   = 'Read data failed when copy data to destfile';
                return false;
            }
            if (false === fwrite($outfp, $buffer)) {
                self::$_lastError   = 'Write data failed when copy data to destfile';
                return false;
            }
            $lastOffset -= $bufferSize;
        }
        fclose($srcfp);
        fclose($outfp);
        return self::RETVAL_CONVERT;
    }
    
    private static function detectAtom($fp) {
        $atomOffset = 0;
        $moovOffset = 0;
        $moovSize   = 0;
        $mdatOffset = 0;
        $mdatSize   = 0;
        $startOffset    = 0;
        $atomFtyp   = null;
        $ftypSize   = 0;
        
        while (!feof($fp)) {
            $buffer = fread($fp, self::ATOM_PREAMBLE_SIZE);
            if (empty($buffer)) {
                break;
            }
            $atomSize   = self::bigEndian32(substr($buffer, 0, 4));
            $atomType   = substr($buffer, 4, 4);
            
            //keep ftyp atom
            if ($atomType === self::ATOM_TYPE_FTYP) {
                $ftypSize   = $atomSize;
                fseek($fp, - self::ATOM_PREAMBLE_SIZE, SEEK_CUR);
                $atomFtyp   = fread($fp, $ftypSize);
                if (empty($atomFtyp)) {
                    self::$_lastError   = 'Read ftypAtom failed';
                    return false;
                }
                $startOffset    = ftell($fp);
            } else {
                /* 64-bit special case */
                if ($atomSize === 1) {
                    $atomBytes  = fread($fp, self::ATOM_PREAMBLE_SIZE);
                    if (empty($atomBytes)) {
                        self::$_lastError   = 'Read atomBytes failed';
                        return false;
                    }
                    $atomSize   = self::bigEndian64(substr($atomBytes, 0, 8));
                    fseek($fp, $atomSize - self::ATOM_PREAMBLE_SIZE * 2, SEEK_CUR);
                } else {
                    fseek($fp, $atomSize - self::ATOM_PREAMBLE_SIZE, SEEK_CUR);
                }
            }
            
            if ($atomType === self::ATOM_TYPE_MOOV) {
                $moovOffset = $atomOffset;
                $moovSize   = $atomSize;
            } else if ($atomType === self::ATOM_TYPE_MDAT) {
                $mdatOffset = $atomOffset;
                $mdatSize   = $atomSize;
            }
            
            if ($atomType !== self::ATOM_TYPE_FREE
                && $atomType !== self::ATOM_TYPE_JUNK
                && $atomType !== self::ATOM_TYPE_MDAT
                && $atomType !== self::ATOM_TYPE_MOOV
                && $atomType !== self::ATOM_TYPE_PNOT
                && $atomType !== self::ATOM_TYPE_SKIP
                && $atomType !== self::ATOM_TYPE_WIDE
                && $atomType !== self::ATOM_TYPE_PICT
                && $atomType !== self::ATOM_TYPE_UUID
                && $atomType !== self::ATOM_TYPE_FTYP) {
                echo 'found atom type ' . $atomType;
                break;
            }
            $atomOffset += $atomSize;
            /* The atom header is 8 (or 16 bytes), if the atom size (which
            * includes these 8 or 16 bytes) is less than that, we won't be
            * able to continue scanning sensibly after this atom, so break. */
            if ($atomSize < 8) {
                break;
            }
        }
        return array(
            'atomOffset'    => $atomOffset,
            'moovOffset'    => $moovOffset,
            'moovSize'      => $moovSize,
            'mdatOffset'    => $mdatOffset,
            'mdatSize'      => $mdatSize,
            'startOffset'   => $startOffset,
            'atomFtyp'      => $atomFtyp,
            'ftypSize'      => $ftypSize
        );
    }
    
    private static function bigEndian32($data) {
        if (strlen($data) != 4) {
            throw new \Exception("Cann't parse data into uint32 not 4 bytes");
        }
        $tmp    = unpack('N', substr($data, 0, 4)); //大端在前
        return $tmp[1];
    }
    
    private static function bigEndian64($data) {
        if (strlen($data) != 8) {
            throw new \Exception("Cann't parse data into uint64 not 8 bytes");
        }
        $tmp    = unpack('J', substr($data, 0, 8));
        return $tmp[1];
    }
    
    private static function bigEndian16($data) {
        if (strlen($data) != 2) {
            throw new \Exception("Cann't parse data into uint16 not 2 bytes");
        }
        $tmp    = unpack('n', substr($data, 0, 2));
        return $tmp[1];
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 文件上传接口
 *
 * @package App\Http\Controllers\Api\v1
 */
class FileController extends Controller
{

    /**
     * 文件上传
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postUploadTemp(Request $request)
    {
        $tempPath = config('path.upload_temp');
        $file = $request->file('file');

        // 判断文件是否有效
        if (!is_object($file) || !$file->isValid()) {
            return $this->error('上传文件时遇到错误');
        }

        $ext = $this->getExt($file);
        $path = $this->getFormatPath();
        $name = $this->getFormatName() . '.' . $ext;

        try {
            // 移动到目录
            $target = $file->move($tempPath . $path, $name);
        } catch (FileException $e) {
            info($e);

            return $this->error('内部错误');
        }

        return $this->created([
            'path' => $path . $name,
            'org_name' => $file->getClientOriginalName(),
            'name' => $name,
            'ext' => $ext,
            'size' => $target->getSize(),
            'url' => upload_url($path . $name, 'temp')
        ], ['Content-Type' => 'text/html']);
    }

    /**
     * 图片裁剪
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postImageCrop(Request $request)
    {
        $data = $request->all();
        $tempPath = config('path.upload_temp');
        $imagePath = array_get($data, 'path');

        // 读取原图片
        try {
            $image = \Image::make($tempPath . $imagePath);
        } catch (\Exception $e) {
            return $this->error('找不到裁剪图片');
        }

        $cropImagePath = 'crop/' . $imagePath;
        $cropImageFullPath = $tempPath . $cropImagePath;
        // 临时目录创建
        if (!$this->createDirIfNotExist(dirname($cropImageFullPath))) {
            return $this->error('目录创建失败');
        }

        // 图片裁剪开始
        $width = intval(array_get($data, 'width'));
        $height = intval(array_get($data, 'height'));
        if ($width && $height) {
            $x = intval(array_get($data, 'x'));
            $y = intval(array_get($data, 'y'));

            $image->crop($width, $height, $x, $y);
        }

        $destWidth = intval(array_get($data, 'destWidth'));
        $destHeight = intval(array_get($data, 'destHeight'));
        if ($destWidth && $destHeight) {
            $image->resize($destWidth, $destHeight);
        }

        // 保存图片并删除原图
        $image->save($cropImageFullPath, 100);
        @unlink($tempPath . $imagePath);

        return $this->created([
            'path' => $cropImagePath,
            'org_name' => array_get($data, 'org_name'),
            'name' => $image->basename,
            'ext' => $image->extension,
            'size' => $image->filesize(),
            'url' => upload_url($cropImagePath, 'temp')
        ]);
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function getExt(UploadedFile $file)
    {
        return $file->getClientOriginalExtension();

//        $ext = $file->guessExtension();
//
//        return $ext ?: $file->getClientOriginalExtension();
    }

    /**
     * 获取格式化目录
     *
     * @return string
     */
    protected function getFormatPath()
    {
        return date('Y/m/d/');
    }

    /**
     * 获取格式化文件名
     *
     * @return string
     */
    protected function getFormatName()
    {
        return uniqid();
    }

    /**
     * 目录不存在时进行创建
     *
     * @param string $directory
     * @return bool
     */
    protected function createDirIfNotExist($directory)
    {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                return false;
            }
        } elseif (!is_writable($directory)) {
            return false;
        }

        return true;
    }
}
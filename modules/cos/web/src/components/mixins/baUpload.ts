import createAxios from '/@/utils/axios'
import { useSiteConfig } from '/@/stores/siteConfig'
import { ElNotification, UploadRawFile } from 'element-plus'
import { randomNum, shortUuid } from '/@/utils/random'
import { fullUrl } from '/@/utils/common'
import { isAdminApp } from '/@/utils/common'
import COS from 'cos-js-sdk-v5'
import type { AxiosRequestConfig, AxiosProgressEvent } from 'axios'
import jsSHA from 'jssha'

export const state = () => {
    const siteConfig = useSiteConfig()
    return siteConfig.upload.mode == 'local' ? 'disable' : 'enable'
}

export async function fileUpload(fd: FormData, _params: anyObj = {}, config: AxiosRequestConfig = {}) {
    const siteConfig = useSiteConfig()
    if (typeof siteConfig.upload.params?.error === 'number') {
        return new Promise((resolve, reject) => {
            reject({
                code: 0,
                data: {},
                msg: siteConfig.upload.params?.message + '(' + siteConfig.upload.params?.code + ')',
                time: Date.now(),
            })
        }) as ApiPromise
    }
    const file = fd.get('file') as UploadRawFile
    const sha1 = await getFileSha1(file)
    const fileKey = getSaveName(file, sha1)

    return new Promise(async (resolve, reject) => {
        const cos = new COS({
            getAuthorization: function (options, callback) {
                createAxios({
                    url: isAdminApp() ? '/admin/Cos/refreshToken' : '/api/Cos/refreshToken',
                    method: 'get',
                }).then((res: any) => {
                    callback({
                        TmpSecretId: res.data.tmpSecretId,
                        TmpSecretKey: res.data.tmpSecretKey,
                        SecurityToken: res.data.sessionToken,
                        StartTime: res.data.startTime,
                        ExpiredTime: res.data.expiredTime,
                    })
                })
            },
        })
        cos.uploadFile({
            Bucket: siteConfig.upload.params?.bucket,
            Region: siteConfig.upload.params?.region,
            Key: fileKey,
            Body: file,
            SliceSize: 1024 * 1024 * 5,
            onFileFinish: function (err, data, options) {
                const fileUrl = '/' + options.Key
                if (!err) {
                    createAxios({
                        url: isAdminApp() ? '/admin/Cos/callback' : '/api/Cos/callback',
                        method: 'POST',
                        data: {
                            url: fileUrl,
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            sha1: sha1,
                        },
                    })
                    resolve({
                        code: 1,
                        data: {
                            file: {
                                full_url: fullUrl(fileUrl),
                                url: fileUrl,
                            },
                        },
                        msg: '',
                        time: Date.now(),
                    })
                } else {
                    ElNotification({
                        type: 'error',
                        message: err.message,
                    })
                    reject({
                        code: 0,
                        data: err,
                        msg: err.message,
                        time: Date.now(),
                    })
                }
            },
            onProgress: (params) => {
                if (typeof config.onUploadProgress == 'function') {
                    config.onUploadProgress(params as unknown as AxiosProgressEvent)
                }
            },
        })
    }) as ApiPromise
}

export function getSaveName(file: UploadRawFile, sha1: string) {
    const fileSuffix = file.name.substring(file.name.lastIndexOf('.') + 1)
    const fileName = file.name.substring(0, file.name.lastIndexOf('.'))
    const dateObj = new Date()

    const replaceArr: anyObj = {
        '{topic}': 'default',
        '{year}': dateObj.getFullYear(),
        '{mon}': ('0' + (dateObj.getMonth() + 1)).slice(-2),
        '{day}': dateObj.getDate(),
        '{hour}': dateObj.getHours(),
        '{min}': dateObj.getMinutes(),
        '{sec}': dateObj.getSeconds(),
        '{random}': shortUuid(),
        '{random32}': randomNum(32, 32),
        '{filename}': fileName.substring(0, 15),
        '{suffix}': fileSuffix,
        '{.suffix}': '.' + fileSuffix,
        '{filesha1}': sha1,
    }
    const replaceKeys = Object.keys(replaceArr).join('|')
    const siteConfig = useSiteConfig()

    const savename = siteConfig.upload.savename[0] == '/' ? siteConfig.upload.savename.slice(1) : siteConfig.upload.savename

    return savename.replace(new RegExp(replaceKeys, 'gm'), (match) => {
        return replaceArr[match]
    })
}

async function getFileSha1(file: UploadRawFile) {
    const shaObj = new jsSHA('SHA-1', 'ARRAYBUFFER')
    shaObj.update(await file.arrayBuffer())
    return shaObj.getHash('HEX')
}

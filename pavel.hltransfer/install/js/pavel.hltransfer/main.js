$(function(){

    const Main = {
        requestUrl: '/bitrix/admin/hltransfer_request.php',
        domNode: {
            exportTab: $('#hl-transfer-export-tab'),
            importTab: $('#hl-transfer-import-tab')
        },

        init() {
            Export.domNode.alertWrap.hide();
            Export.domNode.progressWrap.hide();

            Import.domNode.alertWrap.hide();
            Import.domNode.progressWrap.hide();
            Import.domNode.btnStart.hide();
        }
    };

    const Export = {
        offset: 0,
        errorCount: 0,
        elementCount: 0,
        filePath: null,

        domNode: {
            btnStart:           Main.domNode.exportTab.find('#export-start'),
            progressWrap:       Main.domNode.exportTab.find('#progress-wrap'),
            progress:           Main.domNode.exportTab.find('#progress-line'),
            progressTextBefore: Main.domNode.exportTab.find('#progress-text-before'),
            progressTextAfter:  Main.domNode.exportTab.find('#progress-text-after'),
            alertWrap:          Main.domNode.exportTab.find('#export-alert-wrap'),
            alertText:          Main.domNode.exportTab.find('#export-alert-wrap .ui-alert-message')
        },

        async start() {

            try {
                await this.init();

                if(this.elementCount > 0) {

                    while(this.elementCount > this.offset) {
                        await this.nextStep();
                    }

                    await this.finishStep();
                    this.downloadFile(this.filePath);
                } else {
                    await this.finishStep();
                }
            } catch (e) {
                await this.finishStep();
                this.showMessage(e, 'error');
                this.errorCount++;
            }
        },

        init() {
            return new Promise((resolve, reject) => {

                const context = this;

                this.offset = 0;
                this.errorCount = 0;
                this.elementCount = 0;
                this.filePath = null;

                $.ajax({
                    url: Main.requestUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'initExport'
                    },
                    success(response) {
                        if(response.status === 'success') {
                            context.elementCount = parseInt(response.count) || 0;

                            if(context.elementCount > 0) {
                                context.setProgressValue(0);
                                context.domNode.progressWrap.show();
                                context.domNode.alertWrap.hide();
                                context.domNode.progressTextBefore.text('Подготовка');
                                context.domNode.progressTextAfter.text(context.offset + ' из ' + context.elementCount);
                                context.domNode.btnStart.prop('disabled', true)
                                    .addClass('ui-btn-wait ui-btn-disabled');
                            }

                            resolve();
                        } else {
                            reject();
                        }
                    }
                });
            });
        },

        nextStep() {
            return new Promise((resolve, reject) => {
                const context = this;

                this.domNode.btnStart.prop('disabled', true)
                    .addClass('ui-btn-wait ui-btn-disabled');

                $.ajax({
                    url: Main.requestUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'export',
                        offset: this.offset
                    },
                    success(response) {

                        if (response.status === 'success') {

                            context.filePath = response.filePath;
                            context.offset += parseInt(response.cnt);
                            context.errorCount += parseInt(response.errorCount);

                            const percent = Math.round((context.offset * 100) / context.elementCount);
                            context.setProgressValue(percent);
                            context.domNode.progressTextBefore.text('Процесс экспорта');
                            context.domNode.progressTextAfter.text('Экспортированно ' + context.offset + ' из ' + context.elementCount + ' элементов');

                            resolve();

                        } else {
                            reject();
                        }

                    }
                });
            });
        },

        finishStep() {
            return new Promise((resolve, reject) => {

                const context = this;

                $.ajax({
                    url: Main.requestUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'exportFinish'
                    },
                    success(response) {

                        if (response.status === 'success') {

                            context.domNode.btnStart.prop('disabled', false)
                                .removeClass('ui-btn-wait ui-btn-disabled');


                            let message;
                            if(response.statistic.element > 0) {
                                if(response.statistic.error > 0) {
                                    message = 'Экспорт завершен ошибками. Количество ошибок: ' + response.statistic.error;
                                } else {
                                    message = 'Экспорт успешно завершен!';
                                }
                            } else {
                                message = 'Нет изменений для экспорта';
                            }

                            // let message = (context.errorCount === 0) ? 'Экспорт успешно завершен!' : 'Экспорт завершен ошибками. Количество ошибок: ' + context.errorCount,
                            let messageType = (context.errorCount === 0) ? 'success' : 'warning';
                            context.showMessage(message, messageType);
                            context.domNode.progressTextBefore.text('Экспорт завершен');

                            resolve();

                        } else {
                            reject();
                        }
                    }
                });
            });
        },

        downloadFile(filePath) {
            const link = document.createElement('a');
            link.setAttribute('href', filePath);
            link.setAttribute('download', 'export_hl.txt');
            link.click();
        },

        setProgressValue(percent) {
            percent = parseInt(percent) || 0;
            this.domNode.progress.css('width', percent + '%');
        },

        showMessage(message, type = 'success') {

            let className = 'ui-alert-success';
            switch(type) {
                case 'error':   className = 'ui-alert-danger'; break;
                case 'warning': className = 'ui-alert-warning'; break;
            }

            this.domNode.alertText.text(message);
            this.domNode.alertWrap.removeClass('ui-alert-success ui-alert-warning ui-alert-danger')
                .addClass(className)
                .show();
        },

    };

    const Import = {
        offset: 0,
        errorCount: 0,
        elementCount: 0,

        fileID: null,
        filePath: null,

        domNode: {
            btnStart:           Main.domNode.importTab.find('#import-start'),
            fileInputWrap:      Main.domNode.importTab.find('#hl-transfer-import-file-wrap'),
            fileInput:          Main.domNode.importTab.find('#hl-transfer-import-file'),
            fileName:           Main.domNode.importTab.find('#hl-transfer-import-file-name'),
            fileChoose:         Main.domNode.importTab.find('#hl-transfer-import-file-choose'),
            progressWrap:       Main.domNode.importTab.find('#progress-wrap'),
            progress:           Main.domNode.importTab.find('#progress-line'),
            progressTextBefore: Main.domNode.importTab.find('#progress-text-before'),
            progressTextAfter:  Main.domNode.importTab.find('#progress-text-after'),
            alertWrap:          Main.domNode.importTab.find('#import-alert-wrap'),
            alertText:          Main.domNode.importTab.find('#import-alert-wrap .ui-alert-message')
        },

        async start() {

            try {

                await this.init();
                await this.uploadFile();

                while((this.elementCount === 0 && this.offset === 0) || this.elementCount > this.offset) {
                    await this.nextStep();
                }

                await this.deleteFile();
                await this.finishStep();

            } catch(e) {
                await this.finishStep();
                this.showMessage(e, 'error');
                this.errorCount++;
            }
        },

        init() {
            return new Promise((resolve, reject) => {

                this.offset = 0;
                this.errorCount = 0;
                this.fileID = null;
                this.filePath = null;

                this.setProgressValue(0);
                this.domNode.progressWrap.show();
                this.domNode.alertWrap.hide();
                this.domNode.progressTextBefore.text('Подготовка');
                this.domNode.progressTextAfter.text('');
                this.domNode.fileName.text('');

                this.domNode.btnStart.prop('disabled', true)
                    .addClass('ui-btn-wait ui-btn-disabled');

                $.ajax({
                    url: Main.requestUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'initImport'
                    },
                    success(response) {
                        if(response.status === 'success') {
                            resolve();
                        } else {
                            reject();
                        }
                    }
                });
            });
        },

        nextStep() {
            return new Promise((resolve, reject) => {

                const context = this;

                this.domNode.btnStart.prop('disabled', true)
                    .addClass('ui-btn-wait ui-btn-disabled');

                this.domNode.progressTextBefore.text('Процесс импорта');

                $.ajax({
                    url: Main.requestUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'import',
                        offset: this.offset
                    },
                    success(response) {

                        if (response.status === 'success') {

                            context.elementCount = parseInt(response.allCnt);
                            context.offset += parseInt(response.cnt);
                            context.errorCount += parseInt(response.errorCount);

                            const percent = Math.round((context.offset * 100) / context.elementCount);
                            context.setProgressValue(percent);
                            context.domNode.progressTextAfter.text('Импортировано ' + context.offset + ' из ' + context.elementCount + ' элементов');

                            resolve();

                        } else {
                            reject();
                        }
                    }
                });
            });

        },

        finishStep() {
            return new Promise((resolve, reject) => {

                const context = this;

                $.ajax({
                    url: Main.requestUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'importFinish'
                    },
                    success(response) {

                        if (response.status === 'success') {

                            context.domNode.progressTextBefore.text('Импорт завершен');

                            context.domNode.btnStart.prop('disabled', false)
                                .removeClass('ui-btn-wait ui-btn-disabled');

                            let message = (context.errorCount === 0) ?
                                'Импорт успешно завершен!' :
                                'Импорт завершен ошибками. Количество ошибок: ' + context.errorCount,
                                messageType = (context.errorCount === 0) ? 'success' : 'warning';
                            context.showMessage(message, messageType);
                            context.domNode.progressTextBefore.text('Импорт завершен');

                            context.domNode.fileInput.val('');
                            context.domNode.fileInput.change();

                            resolve();

                        } else {
                            reject();
                        }
                    }
                });
            });
        },

        uploadFile() {
            return new Promise((resolve, reject) => {

                const context = this;
                const file = this.domNode.fileInput.prop('files')[0];

                if(file) {
                    const formData = new FormData();
                    formData.append('action', 'uploadFile');
                    formData.append('file', file);

                    this.domNode.progressTextBefore.text('Загрузка файла');

                    $.ajax({
                        url: Main.requestUrl,
                        type: 'POST',
                        dataType: 'json',
                        processData: false,
                        contentType: false,
                        data: formData,
                        success(response) {

                            if (response.status === 'success') {

                                context.fileID = response.fileID;
                                context.filePath = response.filePath;

                                context.domNode.progressTextBefore.text('Загрузка файла завершена');
                                resolve();

                            } else {
                                reject();
                            }
                        }
                    });
                }
            });
        },

        deleteFile() {
            return new Promise((resolve, reject) => {
                if(this.fileID) {

                    this.domNode.progressTextBefore.text('Удаление временных файлов');

                    $.ajax({
                        url: Main.requestUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'deleteFile',
                            fileID: this.fileID
                        },
                        success(response) {
                            if (response.status === 'success') {
                                resolve();
                            } else {
                                reject();
                            }
                        }
                    });
                } else {
                    resolve();
                }
            });
        },

        setProgressValue(percent) {
            percent = parseInt(percent) || 0;
            this.domNode.progress.css('width', percent + '%');
        },

        showMessage(message, type = 'success') {

            let className = 'ui-alert-success';
            switch(type) {
                case 'error':   className = 'ui-alert-danger'; break;
                case 'warning': className = 'ui-alert-warning'; break;
            }

            this.domNode.alertText.text(message);
            this.domNode.alertWrap.removeClass('ui-alert-success ui-alert-warning ui-alert-danger')
                .addClass(className)
                .show();
        },

    };


    /** Инициализация */
    Main.init();

    /** Клик по кнопке Начать экспорт */
    Export.domNode.btnStart.on('click', (e) => {
        e.preventDefault();

        Export.start().then(() => {});
    });


    /** Клик по кнопке Начать импорт */
    Import.domNode.btnStart.on('click', (e) => {
        e.preventDefault();

        Import.start().then(() => {});
    });


    /**  Изменение файла для импорта */
    Import.domNode.fileInput.on('change', (e) => {
        const file = e.target.files[0],
            fileName = (file) ? file.name : '';

        if(fileName) {
            Import.domNode.btnStart.show(0);
            Import.domNode.fileChoose.hide(0);
        } else {
            Import.domNode.btnStart.hide(0);
            Import.domNode.fileChoose.show(0);
            Import.domNode.fileInputWrap.show(0);
        }
        Import.domNode.fileName.text(fileName);
    });

});

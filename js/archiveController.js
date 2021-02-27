angular.module('archiveViewer').controller('archiveCtrl', function($scope, $http) {

    $scope.title = "Codeparl Angular Archive Viewer";
    $scope.file = null;
    $scope.zipNames = [];
    $scope.content = '';
    $scope.fileName = '';
    $scope.currentZip = '';
    $scope.archiveInfo = [];
    $scope.contentServer = 'server/content-server.php';
    $scope.hasNewFile = false;

    $scope.addInfo = function() {
        (function($, scope) {

            // if (scope.archiveInfo.length > 0)
            for (var key in scope.archiveInfo) {
                var $elm = $('.index-' + key);
                if ($elm.length > 0) {
                    $elm.attr({
                        "data-type": scope.archiveInfo[key]['type'],
                        "data-path": scope.archiveInfo[key]['path'],
                        "data-open": scope.archiveInfo[key]['open'],
                        "data-index": key,
                    });
                    if (scope.archiveInfo[key]['type'] === 'file') {

                        //add file-length info.
                        //there's a problem here of multiple span elements 
                        // so, let's avoid it this way.
                        if ($elm.find('span.file-size.small').length === 0)
                            $('<span>')
                            .addClass('position-absolute file-size small')
                            .html(scope.formatSize(scope.archiveInfo[key]['size']))
                            .appendTo($elm.addClass('position-relative'));
                    }
                }
            }

        })(jQuery, $scope);
    };


    $scope.getZipList = function() {
        $http({
            method: 'GET',
            url: $scope.contentServer,
            params: { zipList: 1 }
        }).then(function(response) {
            $scope.zipNames = response.data.zipList;
            $scope.content = response.data.content;
            $scope.currentZip = $scope.zipNames[0];
            $scope.archiveInfo = response.data.info;
        });
    };

    $scope.getZipList();

    $scope.analyze = function($event) {
        if ($event.target.files.length > 0) {
            $scope.file = $event.target.files[0];
            $scope.fileName = $scope.file.name;
            $scope.hasNewFile = true;
        }

    };

    $scope.uploadFile = function() {
        var fd = new FormData();
        fd.append('file', $scope.file);
        if ($scope.zipNames.length === 0)
            fd.append('archive', 1);

        $http.post($scope.contentServer, fd, {
            transformRequest: angular.identity,
            headers: { 'Content-Type': undefined }
        }).then(function(response) {
            if (response.data.zipName) {
                $scope.zipNames.push(response.data.zipName);
                $scope.hasNewFile = false;
                $scope.fileName = '';
                if (response.data.archiveContent)
                    $scope.content = response.data.archiveContent.content;
            }

        });


    };





    $scope.formatSize = function(size) {
        var kb, mb, gb;
        const M = 1024,
            P = 1;
        kb = Math.fround(size / M);
        mb = Math.fround(kb / M);
        gb = Math.fround(mb / M);

        if (size < M) {
            return size + ' Bytes';
        } else if (kb < M) {
            return kb.toFixed(P) + ' KB';
        } else if (mb < M) {
            return mb.toFixed(P) + ' MB';
        } else if (gb < M) {
            return gb.toFixed(P) + ' GB';
        }
    }

    $scope.getIndexOf = function(elem) {
        var index = elem.classList;
        index = index[index.length - 1];
        index = index.substring(index.lastIndexOf('-') + 1);
        return index;
    };

    $scope.displayAll = function() {
        (function($, scope, http) {
            $('#archiveContent').on('click', '.list-group-item a', function() {

                var $thisButton = $(this);
                var $parent = $thisButton.closest('li.list-group-item');
                var index = index = $parent.data('index'),
                    type = $parent.data('type');

                if (type === 'folder') {
                    if ($parent.data('open')) {
                        $thisButton.next('ul').slideUp('fast', function() {
                            $parent.data('open', false);
                            $parent.find('i').eq(0).toggleClass('fa-minus fa-plus ');
                            $parent.find('i').eq(1).toggleClass('fa-folder-open fa-folder');
                            $(this).remove();
                        });

                        return;
                    } else {
                        $parent.data('open', true);
                        $parent.find('i').eq(0).toggleClass('fa-minus fa-plus');
                        $parent.find('i').eq(1).toggleClass('fa-folder-open fa-folder');
                    }
                }

                http({
                    method: 'GET',
                    url: scope.contentServer,
                    params: {
                        dispaly: index,
                        zipName: scope.currentZip,
                        type: type,
                        path: $parent.data('path'),
                        name: $thisButton.text()
                    }
                }).then(function(res) {

                    //This is not a displayable file so,
                    // allow the user to download it.
                    if (res.data.content.content === null) {
                        var qs = "n=" + scope.currentZip + '&i=' + index;
                        var url = window.location.protocol + '//' + window.location.host;
                        url += '/' + scope.contentServer + '?' + qs;
                        window.open(url, "_top");
                        return;
                    }

                    scope.archiveInfo = res.data.content.info;
                    scope.addInfo();
                    if (type === 'folder') {
                        $(res.data.content.content).insertAfter($thisButton).slideDown('fast')
                        return;
                    }


                    //get the bootstrap modal to display the content
                    http.get('../partials/modal.html').then(function(modal) {
                        scope.modalTitle = scope.archiveInfo[index].name;
                        var $modal = $(modal.data);
                        $modal.modal({ backdrop: 'static' }).appendTo('body')
                            .modal('show').on('hidden.bs.modal', function() {
                                $(this).remove();
                            });
                        $modal.find('.modal-title').text(scope.modalTitle);

                        //handle image file types 
                        if (scope.archiveInfo[index].isText === false && scope.archiveInfo[index].fileType.match(/(jpg|jpeg|png|gif)/ig)) {
                            $modal.find('.modal-body')
                                .html($('<img>')
                                    .attr({ alt: scope.modalTitle, src: res.data.content.content })
                                    .addClass('img-fluid'));
                            return;
                        }

                        //handle textual content
                        if (scope.archiveInfo[index].isText) {
                            var editor = ace.edit('editor');
                            editor.setTheme("ace/theme/tomorrow");
                            editor.session.setMode("ace/mode/" + scope.archiveInfo[index].fileType.toLowerCase());
                            editor.session.setValue(res.data.content.content);
                            editor.setOptions({
                                fontSize: 16,
                                readOnly: true
                            });

                        }
                    });
                }); //then
            });
        })(jQuery, $scope, $http);


    };

    $scope.showZipContent = function($event) {
        $event.preventDefault();
        var index = $event.target.dataset.index;

        $scope.currentZip = $scope.zipNames[index];
        document.
        querySelector('ul.zipList .list-group-item.active')
            .classList
            .remove('active');
        $event.target.parentNode.classList.add('active');
        $http({
            method: "GET",
            url: $scope.contentServer,
            params: { archive: $scope.currentZip }
        }).then(function(response) {
            $scope.content = response.data.archiveContent.content;
            $scope.archiveInfo = response.data.archiveContent.info;
            $scope.addInfo();

        });

    };

    $scope.displayAll();

    $scope.showSidebar = function($event) {

        (function($, scope, event) {

            console.log(event);
            if ($(event.target).is('.show-sidebar , .show-sidebar *')) {
                $('.sidebar').removeClass('slideOutLeft');
                $('.sidebar').addClass('slideInLeft');
            } else {
                $('.sidebar').removeClass('slideInLeft');
                $('.sidebar').addClass('slideOutLeft');
            }

        })(jQuery, $scope, $event);

    }
});
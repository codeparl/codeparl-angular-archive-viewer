var app = angular.module('archiveViewer', ['ngRoute','ngSanitize']);
angular.module("archiveViewer").directive("fileInput",["$parse",function($parse){
    return {
      link: function($scope, $element, $attrs, $ngModelCtrl){
        function createFileInput(){
          var fileInput = document.createElement("input");
          fileInput.type = "file";
          fileInput.accept='.zip';
          fileInput.style.display = 'none';
          angular.element(fileInput).on("change",function(event){
            $scope.$apply(function(){
              $parse($attrs.onChange)($scope, {$event:event});
            })
            //remove old input
            fileInput.remove();
            //create new file input
            createFileInput();
          })
          $element.append(fileInput);
        }
        createFileInput();
      }
    }
  }])
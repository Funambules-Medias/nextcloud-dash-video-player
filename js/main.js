(function (OCA) {
  console.log("DASHVIDEOPLAYERV2: main.js loaded!");
  OCA.Dashvideoplayerv2 = _.extend({}, OCA.Dashvideoplayerv2);

  OCA.AppSettings = null;
  OCA.Dashvideoplayerv2.Mimes = null;

  if (!OCA.Dashvideoplayerv2.AppName) {
    OCA.Dashvideoplayerv2 = {
      AppName: "dashvideoplayerv2",
      frameSelector: null
    };
  }

  OCA.Dashvideoplayerv2.OpenPlayer = function (fileId, filePath) {
    var url = OC.generateUrl(
      "/apps/" + OCA.Dashvideoplayerv2.AppName + "/{fileId}",
      {
        fileId: fileId
      }
    );
    
     /*window.open(url, "Dash Player", "width=1200, height=600");
     return*/

    // create div element
    var divModalMaskExists = document.getElementById("divDashPlayerModal");
    if (!divModalMaskExists) {
      // Create modal mask
      var divModalMask = document.createElement("div");
      divModalMask.id = "divDashPlayerModal";
      divModalMask.setAttribute(
        "style",
        "position:fixed;z-index: 9998;top: 0;left: 0;display: block;width: 100%;height: 100%;background-color: rgba(0,0,0,0.7);"
      );

      // Create modal header
      var divModalHeader = document.createElement("div");
      divModalHeader.setAttribute(
        "style",
        "position: absolute;z-index: 10001;top: 0;right: 0;left: 0;display: flex !important;align-items: center;justify-content: center;width: 100%;height: 50px;transition: opacity 250ms, visibility 250ms; background-color: rgba(0,0,0,0.8);"
      );

      // Create icons menu
      var divIconsMenu = document.createElement("div");
      divIconsMenu.setAttribute(
        "style",
        "position: absolute;right: 0;display: flex;align-items: center;justify-content: flex-end;"
      );

      // append icons menu to modal header
      divModalHeader.appendChild(divIconsMenu);

      // Create close button
      var buttonClose = document.createElement("button");
      buttonClose.type = "button";
      buttonClose.innerHTML = "X";
      buttonClose.setAttribute(
        "style",
        "background-color: transparent; border-color: transparent; color: white; font-size: 16px"
      );

      buttonClose.onclick = function () {        
        document.getElementById("iframeDashPlayerModal").src = "about:blank";
        document.getElementById('divDashPlayerModal').style.display = "none";
      };
      // append button to icons menu
      divIconsMenu.appendChild(buttonClose);

      // Create modal wrapper
      var divModalWrapper = document.createElement("div");
      divModalWrapper.setAttribute(
        "style",
        "display: flex;align-items: center;justify-content: center;box-sizing: border-box;width: 100%;height: 100%;"
      );

      // Create modal container
      var divModalContainer = document.createElement("div");
      divModalContainer.setAttribute(
        "style",
        "display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; cursor: pointer;"
      );
      
      // Close modal when clicking outside the iframe
      divModalContainer.onclick = function(e) {
          if (e.target === divModalContainer) {
            document.getElementById("iframeDashPlayerModal").src = "about:blank";
            document.getElementById('divDashPlayerModal').style.display = "none";
          }
      };

      divModalContainer.innerHTML =
        "<iframe id='iframeDashPlayerModal' width='75%' height='75%' style='border:0; border-radius: 8px; background-color: black; box-shadow: 0 0 20px rgba(0,0,0,0.5);' src='" + url + "'></iframe>";

      // append modal container to modal wrapper
      divModalWrapper.appendChild(divModalContainer);

      // append modal header to modal mask
      divModalMask.appendChild(divModalHeader);

      // append modal wrapper to modal mask
      divModalMask.appendChild(divModalWrapper);

      // append modal mask to document
      document.body.appendChild(divModalMask);
    } else {
      document.getElementById("iframeDashPlayerModal").src = url;
      divModalMaskExists.style.display = "block";
    }
  };

  OCA.Dashvideoplayerv2.GetSettings = function (callbackSettings) {
    if (OCA.Dashvideoplayerv2.Mimes) {
      callbackSettings();
    } else {
      var url = OC.generateUrl(
        "apps/" + OCA.Dashvideoplayerv2.AppName + "/ajax/settings"
      );

      fetch(url, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        credentials: "same-origin"
      })
        .then(function (response) {
          if (response.status !== 200) {
            console.log("Fetch error. Status Code: " + response.status);
            return;
          }

          response.json().then(function (data) {
            OCA.AppSettings = data.settings;
            OCA.Dashvideoplayerv2.Mimes = data.formats;
            callbackSettings();
          });
        })
        .catch(function (err) {
          console.log("Fetch Error: ", err);
        });
    }
  };

  OCA.Dashvideoplayerv2.FileList = {
    attach: function (fileList) {
      console.log("DASHVIDEOPLAYERV2: FileList.attach called for", fileList.id);
      if (fileList.id == "trashbin") {
        return;
      }

      var registerfunc = function () {
        console.log("DASHVIDEOPLAYERV2: registerfunc called. Mimes:", OCA.Dashvideoplayerv2.Mimes);
        if (typeof OCA.Dashvideoplayerv2.Mimes != "object") return;

        for (const ext in OCA.Dashvideoplayerv2.Mimes) {
          attr = OCA.Dashvideoplayerv2.Mimes[ext];
          console.log("DASHVIDEOPLAYERV2: Registering action for " + attr.mime);
          fileList.fileActions.registerAction({
            name: "mpdOpen",
            displayName: t(OCA.Dashvideoplayerv2.AppName, "Play video 1"),
            mime: attr.mime,
            permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE,
            icon: function () {
              return OC.imagePath(OCA.Dashvideoplayerv2.AppName, "app");
            },
            iconClass: "icon-mpd",
            actionHandler: function (fileName, context) {
              var fileInfoModel =
                context.fileInfoModel ||
                context.fileList.getModelForFile(fileName);
              OCA.Dashvideoplayerv2.OpenPlayer(
                fileInfoModel.id,
                OC.joinPaths(context.dir, fileName)
              );
            }
          });

          if (
            attr.mime == "application/mpd" ||
            attr.mime == "application/m3u8"
          ) {
            console.log("DASHVIDEOPLAYERV2: Setting default action for " + attr.mime);
            fileList.fileActions.setDefault(attr.mime, "mpdOpen");
          }
        }
      };

      OCA.Dashvideoplayerv2.GetSettings(registerfunc);
    }
  };

  OCA.Dashvideoplayerv2.DisplayError = function (error) {
    $("#app").text(error).addClass("error");
  };

  var getFileExtension = function (fileName) {
    var extension = fileName
      .substr(fileName.lastIndexOf(".") + 1)
      .toLowerCase();
    return extension;
  };

  var initPage = function () {
    console.log("init.ispubic: ", $("#isPublic").val());
    if ($("#isPublic").val() === "1" && !$("#filestable").length) {
      var fileName = $("#filename").val();
      var mimeType = $("#mimetype").val();
      var extension = getFileExtension(fileName);

      var initSharedButton = function () {
        var formats = OCA.Dashvideoplayerv2.Mimes;
        var config = formats[extension];
        if (!config) {
          return;
        }

        var button = document.createElement("a");
        button.href = OC.generateUrl(
          "apps/" +
            OCA.Dashvideoplayerv2.AppName +
            "/s/" +
            encodeURIComponent($("#sharingToken").val())
        );
        button.className = "button";
        button.innerText = t(OCA.Dashvideoplayerv2.AppName, "Play video");
        $("#preview").append(button);
      };

      OCA.Dashvideoplayerv2.GetSettings(initSharedButton);
    } else {
      console.log("DASHVIDEOPLAYERV2: Registering plugin...");
      
      var registerFileActions = function () {
        console.log("DASHVIDEOPLAYERV2: registerFileActions called");

        // 1. Global Capture Event Listener (The "Hammer" approach)
        if (!window.dashPlayerListenerAttached) {
            console.log("DASHVIDEOPLAYERV2: Attaching global capture click listener");
            document.addEventListener('click', function(e) {
                var target = e.target;
                
                // DEBUG: Log click details if inside file list to help debug structure
                if (target.closest && (target.closest('#fileList') || target.closest('.files-file-list') || target.closest('tbody') || target.closest('.grid-view'))) {
                    console.log('DASHVIDEOPLAYERV2: Click inside file list on:', target.tagName, target.className, target);
                }

                var row = null;
                var current = target;
                
                // Walk up to find the file row
                while (current && current !== document) {
                    if (current.tagName === 'TR' || (current.classList && current.classList.contains('file-row')) || (current.classList && current.classList.contains('oc-dialog-filepicker__file'))) {
                        row = current;
                        break;
                    }
                    current = current.parentNode;
                }
                
                if (row) {
                    // 1. Get Filename
                    var fileName = row.getAttribute('data-file');
                    // Strategy: data-cy-files-list-row-name (Vue/Cypress)
                    if (!fileName) fileName = row.getAttribute('data-cy-files-list-row-name');
                    
                    if (!fileName) {
                        var nameEl = row.querySelector('.nametext, .innernametext, .name');
                        if (nameEl) fileName = nameEl.innerText;
                        else if (target.classList.contains('files-list__row-name-link')) fileName = target.innerText;
                    }
                    
                    if (fileName) {
                        fileName = fileName.replace(/[\n\r]+/g, '').trim(); // Clean up whitespace
                    } else {
                        console.log('DASHVIDEOPLAYERV2: Could not determine filename for row', row);
                    }

                    // 2. Get File ID (The hard part)
                    var fileId = row.getAttribute('data-id');
                    
                    // Strategy: data-cy-files-list-row-fileid (Vue/Cypress)
                    if (!fileId) fileId = row.getAttribute('data-cy-files-list-row-fileid');
                    
                    // Strategy A: Look for data-e2e-file-id (common in Vue)
                    if (!fileId) fileId = row.getAttribute('data-e2e-file-id');
                    
                    // Strategy B: Look for any link with fileId in href
                    if (!fileId) {
                        var links = row.querySelectorAll('a[href*="fileId="], a[href*="fileid="]');
                        if (links.length > 0) {
                            var match = /fileId=(\d+)/i.exec(links[0].href);
                            if (match) fileId = match[1];
                        }
                    }

                    // Strategy C: Regex search the entire row HTML for anything looking like an ID
                    if (!fileId) {
                        var html = row.outerHTML;
                        // Look for data-id="123" or fileId: 123
                        var idMatch = /data-id="(\d+)"/.exec(html);
                        if (idMatch) fileId = idMatch[1];
                        
                        if (!fileId) {
                             idMatch = /fileId=(\d+)/.exec(html);
                             if (idMatch) fileId = idMatch[1];
                        }
                    }
                    
                    // Strategy D: Check row ID (e.g. fileList_row_12345)
                    if (!fileId && row.id) {
                        var idParts = row.id.split('_');
                        var lastPart = idParts[idParts.length - 1];
                        if (/^\d+$/.test(lastPart)) {
                            fileId = lastPart;
                        }
                    }

                    if (fileName && (fileName.toLowerCase().endsWith('.mpd') || fileName.toLowerCase().endsWith('.m3u8'))) {
                        console.log("DASHVIDEOPLAYERV2: Intercepted click on '" + fileName + "'");
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        if (fileId) {
                            console.log("DASHVIDEOPLAYERV2: Found File ID in DOM:", fileId);
                            OCA.Dashvideoplayerv2.OpenPlayer(fileId, null);
                        } else {
                            console.log("DASHVIDEOPLAYERV2: ID not found in DOM, attempting WebDAV lookup...");
                            
                            // 1. Determine current directory
                            var dir = '/';
                            var urlParams = new URLSearchParams(window.location.search);
                            if (urlParams.has('dir')) {
                                dir = urlParams.get('dir');
                            } else {
                                var dirInput = document.getElementById('dir');
                                if (dirInput) dir = dirInput.value;
                            }
                            
                            // 2. Construct path
                            var path = dir + '/' + fileName;
                            path = path.replace('//', '/'); // Normalize
                            
                            console.log("DASHVIDEOPLAYERV2: Looking up path:", path);
                            
                            // 3. WebDAV PROPFIND
                            var user = OC.getCurrentUser().uid;
                            
                            // Construct WebDAV URL
                            // Format: /remote.php/dav/files/{user}/{path}
                            var davUrl = OC.getRootPath() + '/remote.php/dav/files/' + encodeURIComponent(user);
                            
                            // Split and encode path components to handle spaces and special chars correctly
                            var pathParts = path.split('/');
                            for (var i = 0; i < pathParts.length; i++) {
                                if (pathParts[i]) davUrl += '/' + encodeURIComponent(pathParts[i]);
                            }

                            console.log("DASHVIDEOPLAYERV2: WebDAV URL:", davUrl);
                            
                            var headers = {
                                'Depth': '0',
                                'Content-Type': 'application/xml'
                            };
                            
                            // CRITICAL: Add CSRF Token
                            if (OC.requestToken) {
                                headers['requesttoken'] = OC.requestToken;
                                headers['OCS-APIRequest'] = 'true';
                            } else {
                                console.warn("DASHVIDEOPLAYERV2: OC.requestToken is missing!");
                            }

                            // Explicitly request oc:id
                            var body = '<?xml version="1.0"?>' +
                                       '<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">' +
                                       '  <d:prop>' +
                                       '    <oc:id />' +
                                       '    <oc:fileid />' +
                                       '  </d:prop>' +
                                       '</d:propfind>';

                            fetch(davUrl, {
                                method: 'PROPFIND',
                                headers: headers,
                                body: body,
                                credentials: 'same-origin'
                            }).then(function(response) {
                                if (response.ok) {
                                    return response.text();
                                }
                                throw new Error('WebDAV lookup failed: ' + response.status);
                            }).then(function(xmlText) {
                                var parser = new DOMParser();
                                var xmlDoc = parser.parseFromString(xmlText, "text/xml");
                                
                                // 1. Try standard namespace method for oc:id
                                var idNode = xmlDoc.getElementsByTagNameNS('http://owncloud.org/ns', 'id')[0];
                                if (idNode) fileId = idNode.textContent;
                                
                                // 2. Try oc:fileid
                                if (!fileId) {
                                    idNode = xmlDoc.getElementsByTagNameNS('http://owncloud.org/ns', 'fileid')[0];
                                    if (idNode) fileId = idNode.textContent;
                                }

                                // 3. Fallback: Regex on the text (ignores namespaces issues)
                                if (!fileId) {
                                    var match = /<oc:id>([^<]+)<\/oc:id>/.exec(xmlText);
                                    if (match) fileId = match[1];
                                }
                                
                                if (!fileId) {
                                    var match = /<oc:fileid>([^<]+)<\/oc:fileid>/.exec(xmlText);
                                    if (match) fileId = match[1];
                                }

                                if (fileId) {
                                    console.log("DASHVIDEOPLAYERV2: WebDAV found ID:", fileId);
                                    OCA.Dashvideoplayerv2.OpenPlayer(fileId, null);
                                } else {
                                     console.error("DASHVIDEOPLAYERV2: WebDAV response did not contain oc:id");
                                     console.log("DASHVIDEOPLAYERV2: XML Response:", xmlText);
                                }
                            }).catch(function(err) {
                                console.error("DASHVIDEOPLAYERV2: WebDAV lookup error", err);
                                alert("Error: Could not determine file ID for " + fileName + ". See console for details.");
                            });
                        }
                    }
                }
            }, true); // Capture phase!
            window.dashPlayerListenerAttached = true;
        }

        // 2. Try New API (Nextcloud 28+)
        if (window.Nextcloud && window.Nextcloud.Files && window.Nextcloud.Files.registerFileAction) {
            console.log("DASHVIDEOPLAYERV2: Registering file action via window.Nextcloud.Files");
            
            var openDashPlayer = function(nodes) {
                var node = nodes[0];
                var fileId = node.fileId || node.id;
                var fileName = node.name || node.basename;
                var dir = node.path ? node.path.substring(0, node.path.lastIndexOf('/')) : '/';
                
                console.log("DASHVIDEOPLAYERV2: Opening player (New API) for", fileId, fileName, dir);
                OCA.Dashvideoplayerv2.OpenPlayer(fileId, null);
            };

            // Register for each mime type
            for (const ext in OCA.Dashvideoplayerv2.Mimes) {
                var attr = OCA.Dashvideoplayerv2.Mimes[ext];
                
                window.Nextcloud.Files.registerFileAction({
                    id: 'dashvideoplayerv2-open-' + ext,
                    displayName: t(OCA.Dashvideoplayerv2.AppName, "Play video"),
                    icon: 'icon-mpd',
                    order: -100,
                    enabled: function(nodes) {
                        return nodes && nodes.length === 1 && nodes[0].mime === attr.mime;
                    },
                    exec: openDashPlayer
                });
            }
        }

        // 3. Legacy API
        if (OCA.Files && OCA.Files.fileActions) {
            var appName = OCA.Dashvideoplayerv2.AppName;
            
            // Iterate over your mimes
            for (const ext in OCA.Dashvideoplayerv2.Mimes) {
                var attr = OCA.Dashvideoplayerv2.Mimes[ext];
                console.log("DASHVIDEOPLAYERV2: Registering action for " + attr.mime);
                
                OCA.Files.fileActions.registerAction({
                    name: 'mpdOpen',
                    displayName: t(appName, "Play video"),
                    mime: attr.mime,
                    permissions: OC.PERMISSION_READ,
                    iconClass: 'icon-mpd', 
                    actionHandler: function (fileName, context) {
                        // Context handling for NC28+
                        var dir = context.dir;
                        if (!dir && context.fileList && context.fileList.getCurrentDirectory) {
                                dir = context.fileList.getCurrentDirectory();
                        }
                        
                        var fileId = context.fileId || (context.fileInfoModel ? context.fileInfoModel.id : null);
                        
                        // Fallback to get ID if missing
                        if (!fileId && context.$file) {
                            fileId = context.$file.attr('data-id');
                        }

                        console.log("DASHVIDEOPLAYERV2: Opening player for", fileId, fileName, dir);

                        if (fileId && dir) {
                            OCA.Dashvideoplayerv2.OpenPlayer(
                                fileId,
                                OC.joinPaths(dir, fileName)
                            );
                        } else {
                            console.error("DASHVIDEOPLAYERV2: Could not determine fileId or dir", {fileId, dir, context});
                        }
                    }
                });

                // Set default action if needed
                if (attr.mime === "application/mpd" || attr.mime === "application/m3u8") {
                    OCA.Files.fileActions.setDefault(attr.mime, 'mpdOpen');
                }
            }
        }
      };

      // Wait for DOM and Settings
      $(document).ready(function () {
          OCA.Dashvideoplayerv2.GetSettings(registerFileActions);
      });
    }
  };

  initPage();
})(OCA);

/*
 * A little bit of a hack - changing file icon...
 */
$(document).ready(function () {
  PluginDashvideoplayerv2_ChangeIconsNative = function () {
    $("#filestable")
      .find("tr[data-type=file]")
      .each(function () {
        if (
          ($(this).attr("data-mime") == "application/mpd" ||
            $(this).attr("data-mime") == "application/m3u8") &&
          $(this).find("div.thumbnail").length > 0
        ) {
          if ($(this).find("div.thumbnail").hasClass("icon-mpd") == false) {
            $(this).find("div.thumbnail").addClass("icon icon-mpd");
          }
        }
      });
  };

  if ($("#filesApp").val()) {
    $("#app-content-files")
      .add("#app-content-extstoragemounts")
      .on("changeDirectory", function (e) {
        if (OCA.AppSettings == null) return;
        PluginDashvideoplayerv2_ChangeIconsNative();
      })
      .on("fileActionsReady", function (e) {
        if (OCA.AppSettings == null) return;
        PluginDashvideoplayerv2_ChangeIconsNative();
      });
  }
});

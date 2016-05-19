/**
 * Created by James on 11/13/2015.
 */


var viewer;

require.config({
    baseUrl : 'libs',
    waitSeconds : 60
});


requirejs(["cesium/Cesium"], function(Cesium) {

    var h= 0;

//Create a Viewer instances and add the DataSource.
    viewer = new Cesium.Viewer('cesiumContainer', {
        animation : false,
        timeline : false,
        geocoder : false,
        //sceneModePicker : false,
        navigationInstructionsInitiallyVisible: false,
        skyAtmosphere: false,
        skyBox: false,
        baseLayerPicker : true
        //terrainProvider: new EllipsoidTerrainProvider()
    });

    var data = Cesium.CzmlDataSource.load('attenderczml.php');

    var flyHome = function () {
        viewer.camera.flyTo({
            destination: Cesium.Cartesian3.fromDegrees(-75.169899, 39.947262, 85000.0),
            duration:4
        });
    };

    
    viewer.homeButton.viewModel._command = Cesium.createCommand(flyHome);

    viewer.dataSources.add(data).then(flyHome);


    viewer.baseLayerPicker.viewModel.selectedImagery = viewer.baseLayerPicker.viewModel.imageryProviderViewModels[11];


});
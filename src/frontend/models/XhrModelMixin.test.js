const XhrMixin = require('./XhrModelMixin');
const EventMixin = require('./EventMixin');
const getXHRMock = require('../mocks/getXHRMock.fn');

function addModelFunctionsMocks(xhrModel) {
    xhrModel.makeRequestUrl = function () {
        return '/test';
    };

    xhrModel.isLoaded = function () {
        return false;
    };

    xhrModel = Object.assign(xhrModel, EventMixin);

    return xhrModel;
}

function getXhrModel(xhrMock) {
    if (!xhrMock) {
        xhrMock = getXHRMock('{"test": "123"}');
    }

    let xhrModel = Object.create(XhrMixin);
    xhrModel = addModelFunctionsMocks(xhrModel);

    xhrModel.initXhr(xhrMock);
    xhrModel.initEvents();

    return xhrModel;
}

test('XhrMixin.interface', function () {
    let xhrModel = Object.create(XhrMixin);

    expect(xhrModel.initXhr).toBeInstanceOf(Function);
    expect(xhrModel.bindXhrEvents).toBeInstanceOf(Function);
    expect(xhrModel.handleLoad).toBeInstanceOf(Function);
    expect(xhrModel.handleLoadError).toBeInstanceOf(Function);
    expect(xhrModel.load).toBeInstanceOf(Function);
    expect(xhrModel.loadCustomUrl).toBeInstanceOf(Function);
});

test('XhrMixin.load', function () {
    let loadHandler = jest.fn();
    let errorHandler = jest.fn();
    let xhrMock = getXHRMock('{"test": "123"}');
    let xhrModel = getXhrModel(xhrMock);
    xhrModel.addEventListener('load', loadHandler);
    xhrModel.addEventListener('loadError', errorHandler);

    xhrMock.responseType = 'load';
    xhrModel.load();
    expect(loadHandler).toHaveBeenCalledTimes(1);

    xhrMock.responseType = 'error';
    xhrModel.load();
    expect(errorHandler).toHaveBeenCalledTimes(1);
});

test('XhrMixin.loadCustomUrl', function () {
    let loadHandler = jest.fn();
    let errorHandler = jest.fn();
    let expectedUrl = 'http://example.com/test';
    let xhrMock = getXHRMock('{"test": "123"}');
    xhrMock.open = jest.fn();
    let xhrModel = getXhrModel(xhrMock);

    xhrModel.addEventListener('load', loadHandler);
    xhrModel.addEventListener('loadError', errorHandler);

    xhrMock.responseType = 'load';
    xhrModel.loadCustomUrl(expectedUrl);
    expect(loadHandler).toHaveBeenCalledTimes(1);

    xhrMock.responseType = 'error';
    xhrModel.loadCustomUrl(expectedUrl);
    expect(errorHandler).toHaveBeenCalledTimes(1);

    expect(xhrMock.open).toHaveBeenCalledTimes(2);
    expect(xhrMock.open.mock.calls[0][0]).toBe('GET');
    expect(xhrMock.open.mock.calls[0][1]).toBe(expectedUrl);
    expect(xhrMock.open.mock.calls[1][0]).toBe('GET');
    expect(xhrMock.open.mock.calls[1][1]).toBe(expectedUrl);
});

test('XhrMixin.load (двойной вызов)', function () {
    let loadHandler = jest.fn();
    let xhrMock = getXHRMock('{"test": "123"}', true);
    let waitLoadMs = 150;

    let xhrModel = getXhrModel(xhrMock);
    xhrModel.addEventListener('load', loadHandler);
    xhrMock.responseType = 'load';

    return new Promise(function (resolve, reject) {
        xhrModel.load();
        xhrModel.load();

        setTimeout(function () {
            try {
                expect(loadHandler).toHaveBeenCalledTimes(1);
                resolve();
            }
            catch (exception) {
                reject(exception);
            }
        }, waitLoadMs);
    });
});
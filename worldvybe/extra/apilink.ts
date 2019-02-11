import { Injectable } from '@angular/core';
import { BaseRequestOptions, RequestOptions, RequestOptionsArgs } from '@angular/http';
import 'rxjs/add/operator/map';
// import { map } from 'rxjs/operators';

/*
  Generated class for the ApilinkProvider provider.

  See https://angular.io/docs/ts/latest/guide/dependency-injection.html
  for more info on providers and Angular DI.
*/
@Injectable()
export class ApilinkProvider extends BaseRequestOptions {

  merge(options?: RequestOptionsArgs): RequestOptions {
  
    return new CommonRequestOptions(super.merge(extracted(options)));
  }
}

/**
 * for inner merge when using post put patch delete...others method
 */
export class CommonRequestOptions extends RequestOptions {
  merge(options?: RequestOptionsArgs): RequestOptions {
    
    return new RequestOptions(super.merge(extracted(options)));
  }
}

/**
 * inject default values
 *
 * @param options
 * @returns {RequestOptionsArgs}
 */
export function extracted(options: RequestOptionsArgs) {
  if (!validUrl(options.url)) {

    
     
    options.url = 'http://13.59.189.135:3002/' + (options.url ? options.url : "");
  }
  

  return options;
}

/**
 * validate url
 *
 * @param url
 * @returns {boolean}
 */
export function validUrl(url: string) {
  return /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/.test(url);
}
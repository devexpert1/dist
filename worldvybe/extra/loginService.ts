import {Http,URLSearchParams} from '@angular/http';
import {Injectable} from '@angular/core';
import 'rxjs/Rx';

@Injectable()
export class LoginService{
  constructor(private http:Http){}

 Login(formdata) {
 return this.http
   .post('adminLogin', formdata)
     .map((data)=>{
           return data.json();
     }, error => {
        return error.json();
     });
}  

 Signup(formdata) {
 return this.http
   .post('adminSignup', formdata)
     .map((data)=>{
           return data.json();
     }, error => {
        return error.json();
     });
}
 ForgotPassword(formdata) {
 return this.http
   .post('adminForgotPassword', formdata)
     .map((data)=>{
           return data.json();
     }, error => {
        return error.json();
     });
}

 UploadImage(formdata) {
 return this.http
   .post('uploadImage', formdata)
     .map((data)=>{
           return data.json();
     }, error => {
        return error.json();
     });
}

 UpdateProfile(formdata, id) {
 return this.http
   .post('updateAdminProfile/'+ id, formdata)
     .map((data)=>{
           return data.json();
     }, error => {
        return error.json();
     });
}

GetProfile(profileData) {
 return this.http
   .post('getAdminProfile', profileData)
     .map((data)=>{
           return data.json();
     }, error => {
        return error.json();
     });
}

 UpdatePassword(formdata) {
 return this.http
   .post('updateAdminPassword', formdata)
     .map((data)=>{
           return data.json();
     }, error => {
        return error.json();
     });
}
}; 
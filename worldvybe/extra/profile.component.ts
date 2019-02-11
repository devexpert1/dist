import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { LoginService } from '../services/loginService';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css']
})
export class ProfileComponent implements OnInit {
  authForm: FormGroup;
  authForm1: FormGroup;
  profile: any;
  selectedFile: any;
  preview: any;
  imagePreview: any;
  imageURL: any;
  constructor(public fb: FormBuilder, public loginService: LoginService, private toastr: ToastrService) {
    this.createForm();
    this.getAdminProfile();

    this.preview = false;
    this.imageURL = 'http://13.59.189.135:3002/images/';
  }

  ngOnInit() {

  }
  onFileChanged(event, image) {
    console.log(image);
    this.preview = true;
    var selectedFile = event.target.files[0];
    this.selectedFile = selectedFile;
    this.authForm.get('image').setValue(selectedFile);
    console.log(event.target, event.target.files[0])
    const reader = new FileReader();
    reader.onload = () => {
      this.imagePreview = reader.result;
      console.log(this.imagePreview)
    };
    reader.readAsDataURL(selectedFile);

  }


  getAdminProfile() {
    var profileData = {
      adminId: localStorage.getItem('adminId')
    };
    this.loginService.GetProfile(profileData).subscribe((response) => {
      console.log(response);
      this.profile = response[0];
      this.authForm.patchValue({
        firstname: this.profile.firstname,
        lastname: this.profile.lastname,
        email: this.profile.email,
        contact: this.profile.contact,
        address: this.profile.address,
        city: this.profile.city,
        country: this.profile.country,
        zipcode: this.profile.zipcode,
        image: this.profile.image,
      });
    })
  }

  createForm() {
    this.authForm = this.fb.group({
      firstname: ['', Validators.compose([Validators.required])],
      lastname: ['', Validators.compose([Validators.required])],
      email: ['', Validators.compose([Validators.required, Validators.pattern('[A-Za-z0-9._%+-]{3,}@[a-zA-Z]{3,}([.]{1}[a-zA-Z]{2,}|[.]{1}[a-zA-Z]{2,}[.]{1}[a-zA-Z]{2,})')])],
      contact: ['', Validators.compose([Validators.required])],
      address: ['', Validators.compose([Validators.required])],
      city: ['', Validators.compose([Validators.required])],
      country: ['', Validators.compose([Validators.required])],
      zipcode: ['', Validators.compose([Validators.required])],
      image: ['']
    });
    this.authForm1 = this.fb.group({
      password: ['', Validators.compose([Validators.required])],
      newPassword: ['', Validators.compose([Validators.required])],
      confirmPassword: ['', Validators.compose([Validators.required])]
    });

  };

  //  private prepareSave(): any {
  //   let input = new FormData(); 
  //   input.append('image', this.authForm.get('image').value);


  // }


  updateProfile() {
    var adminId = localStorage.getItem('adminId');
    console.log(this.authForm.value, adminId);
    if (this.preview == true) {
      const uploadFormData = new FormData();
      uploadFormData.append('image', this.selectedFile, this.selectedFile.name);
      this.loginService.UploadImage(uploadFormData).subscribe((response) => {
        console.log(response);
        var profileData = {
          address: this.authForm.value.address,
          city: this.authForm.value.city,
          contact: this.authForm.value.contact,
          country: this.authForm.value.country,
          email: this.authForm.value.email,
          firstname: this.authForm.value.firstname,
          lastname: this.authForm.value.lastname,
          zipcode: this.authForm.value.zipcode,
          image: response
        }
        console.log(profileData);
        this.loginService.UpdateProfile(profileData, adminId).subscribe((response) => {
          console.log(response);
          this.toastr.success('Profile updated successfully!');

        })

      })
    } else {
      var profileData = {
        address: this.authForm.value.address,
        city: this.authForm.value.city,
        contact: this.authForm.value.contact,
        country: this.authForm.value.country,
        email: this.authForm.value.email,
        firstname: this.authForm.value.firstname,
        lastname: this.authForm.value.lastname,
        zipcode: this.authForm.value.zipcode,
        image: this.authForm.value.image
      }
      console.log(profileData);
      this.loginService.UpdateProfile(profileData, adminId).subscribe((response) => {
        console.log(response);
        this.toastr.success('Profile updated successfully!');

      })
    }

  }

  updatePassword() {
    var passwordData = {
      adminId: localStorage.getItem('adminId'),
      newPassword: this.authForm1.value.newPassword
    };
    if (this.authForm1.value.password == '') {

    } else if (this.authForm1.value.newPassword == '') {

    } else if (this.authForm1.value.confirmPassword == '') {

    } else if (this.authForm1.value.newPassword != this.authForm1.value.confirmPassword) {
      this.toastr.error('Your new password and confirm password does not match!');
    } else if (this.authForm1.value.password != localStorage.getItem('password')) {
      this.toastr.error('Your current password is incorrect!');
    } else {
      this.loginService.UpdatePassword(passwordData).subscribe((response) => {
        console.log(response);
        localStorage.setItem('password', response[0].password);
        this.toastr.success('Your password updated successfully!');

      })
    }

  }

}

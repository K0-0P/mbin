import './bootstrap'
import './styles/app.scss';
import KChoices from "./utils/choices";
import KDatepicker from "./utils/datepicker";
import Navbar from "./utils/navbar";
import KMasonry from './utils/masonry';

window.addEventListener('load', function (event) {
    const choices = new KChoices();
    const datepicker = new KDatepicker();
    const navbar = new Navbar();
    const masonry = new KMasonry();
});

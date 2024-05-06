import './bootstrap';
import DataTable from 'datatables.net-dt';

let usertable = new DataTable('#usersTable', {
    select: true
});

let customerTable = new DataTable('#customerTable', {
    select: true,
    responsive: true
});

let coursetable = new DataTable('#courseTable', {
    select: true,
    scrollX: true,
});

let agntable = new DataTable('#agnTable', {
    select: true,
    scrollX: true,
});

let brntable = new DataTable('#brnTable', {
    select: true
});

let dpmtable = new DataTable('#dpmTable', {
    select: true
});

let permtable = new DataTable('#permTable', {
    select: true
});

let roletable = new DataTable('#roleTable', {
    select: true
});

let upermTable = new DataTable('#upermTable', {
    select: true
});

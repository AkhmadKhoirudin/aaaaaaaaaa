import http from 'k6/http';
import { sleep } from 'k6';

export let options = {
    vus: 20,          // 20 virtual users
    duration: "30s",  // jalankan 30 detik
};

export default function () {
    http.get("https://voymsjeqmvwrryetfldy.supabase.co/rest/v1/team_members", {
        headers: {
            "apikey": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZveW1zamVxbXZ3cnJ5ZXRmbGR5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjM5NjYyMjYsImV4cCI6MjA3OTU0MjIyNn0.dn7TyQ20uZGJzW4kn3cV7HozzXMSL1iWSC4vA99WFLA",
            "Authorization": "Bearer eyJhbGciOiJIUzI1NiIsImtpZCI6IjdKbndiZ0I3TmIvcDhLQlgiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL3ZveW1zamVxbXZ3cnJ5ZXRmbGR5LnN1cGFiYXNlLmNvL2F1dGgvdjEiLCJzdWIiOiI2ZTMyZTRkMy0yNWU3LTQ3ZDUtOTdiNS02MDk1NTk2OWUyMmYiLCJhdWQiOiJhdXRoZW50aWNhdGVkIiwiZXhwIjoxNzY0MDgwNzY2LCJpYXQiOjE3NjQwNzcxNjYsImVtYWlsIjoiYWtobWFkd2lidTA1QGdtYWlsLmNvbSIsInBob25lIjoiIiwiYXBwX21ldGFkYXRhIjp7InByb3ZpZGVyIjoiZW1haWwiLCJwcm92aWRlcnMiOlsiZW1haWwiLCJnaXRodWIiXX0sInVzZXJfbWV0YWRhdGEiOnsiYXZhdGFyX3VybCI6Imh0dHBzOi8vYXZhdGFycy5naXRodWJ1c2…iaXNzIjoiaHR0cHM6Ly9hcGkuZ2l0aHViLmNvbSIsIm5hbWUiOiJha2htYWRraG9pcnVkaW4iLCJwaG9uZV92ZXJpZmllZCI6ZmFsc2UsInByZWZlcnJlZF91c2VybmFtZSI6IkFraG1hZEtob2lydWRpbiIsInByb3ZpZGVyX2lkIjoiOTkxNTY0MzYiLCJzdWIiOiI5OTE1NjQzNiIsInVzZXJfbmFtZSI6IkFraG1hZEtob2lydWRpbiJ9LCJyb2xlIjoiYXV0aGVudGljYXRlZCIsImFhbCI6ImFhbDEiLCJhbXIiOlt7Im1ldGhvZCI6Im9hdXRoIiwidGltZXN0YW1wIjoxNzY0MDc3MTY2fV0sInNlc3Npb25faWQiOiI2YmZkYWFjZS1lMmNjLTRmZDEtYWQ4Yi01NjQ2MDE5NmRiZDIiLCJpc19hbm9ueW1vdXMiOmZhbHNlfQ.PDfkjjR9RUpWsh2D3KCOlhJbN-qqEU1epe4dssgh284"
        }
    });

    sleep(1);
}

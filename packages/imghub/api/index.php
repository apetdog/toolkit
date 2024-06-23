<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="/public/logo.png" type="image/x-icon" />
  <title>Toolkit | ImgHub API Documentation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script type="importmap">
      {
        "imports": {
          "vue": "https://unpkg.com/vue@3/dist/vue.esm-browser.prod.js"
        }
      }
    </script>
</head>

<body class="bg-gray-100">
  <div id="app">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">ImgHub API Documentation</h1>

      <div v-for="endpoint in endpoints" :key="endpoint.name" class="mb-8 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-semibold mb-4">{{ endpoint.name }}</h2>
        <div class="mb-4">
          <span class="font-bold" :class="methodColor(endpoint.method)">{{ endpoint.method }}</span>
          <span class="pl-4 font-mono">{{ endpoint.url }}</span>
        </div>
        <h3 class="font-semibold mb-2">Request:</h3>
        <pre class="bg-gray-100 p-3 rounded mb-4">{{ endpoint.request }}</pre>
        <h3 class="font-semibold mb-2">Response:</h3>
        <pre class="bg-gray-100 p-3 rounded">{{ endpoint.response }}</pre>
      </div>
    </div>
  </div>

  <script type="module">
    import { createApp, ref } from "vue";

    const app = {
      setup() {
        const endpoints = ref([
          {
            name: "List Images",
            method: "GET",
            url: "/imghub/api/list?limit=10&marker=OPTIONAL_MARKER",
            request: "No request body",
            response: JSON.stringify({
              success: true,
              images: [
                {
                  url: "https://example.com/image.jpg",
                  filename: "image.jpg",
                  key: "uploads/image.jpg"
                }
              ],
              isTruncated: true,
              nextMarker: "NEXT_MARKER"
            }, null, 2)
          },
          {
            name: "Upload Image",
            method: "POST",
            url: "/imghub/api/upload",
            request: "Content-Type: multipart/form-data\n\nBody:\nimage: [FILE]",
            response: JSON.stringify({
              success: true,
              message: "Image uploaded successfully"
            }, null, 2)
          },
          {
            name: "Delete Image",
            method: "DELETE",
            url: "/imghub/api/delete/uploads%2Fimage.jpg",
            request: "No request body",
            response: JSON.stringify({
              success: true,
              message: "Image deleted successfully"
            }, null, 2)
          }
        ]);

        const methodColor = (method) => {
          const colors = {
            GET: "text-green-600",
            POST: "text-blue-600",
            DELETE: "text-red-600"
          };
          return colors[method] || "text-gray-600";
        };

        return {
          endpoints,
          methodColor
        };
      }
    };

    createApp(app).mount("#app");
  </script>
</body>

</html>
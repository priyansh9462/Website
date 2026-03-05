export const handleApiError = (error) => {
  if (error.response) {
    // The request was made and the server responded with a status code
    console.error('API Error Response:', error.response.data);
    console.error('Status:', error.response.status);
    console.error('Headers:', error.response.headers);
    
    return {
      message: error.response.data?.message || 'An error occurred',
      status: error.response.status,
    };
  } else if (error.request) {
    // The request was made but no response was received
    console.error('API Error Request:', error.request);
    return {
      message: 'No response from server',
      status: null,
    };
  } else {
    // Something happened in setting up the request
    console.error('API Error:', error.message);
    return {
      message: error.message,
      status: null,
    };
  }
};
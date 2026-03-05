import { useState, useEffect } from 'react';
import Header from '@/components/Header';
import Hero from '@/components/Hero';
import CoursesSection from '@/components/CoursesSection';
import AboutSection from '@/components/AboutSection';
import AboutLeadership from '@/components/about/AboutLeadership';
// import AboutLeadership2 from '@/components/about/AboutLeadership2';
import TestimonialsSection from '@/components/TestimonialsSection';
import Footer from '@/components/Footer';
import EnquiryModal from '@/components/EnquiryModal';
import NewsAlert from '@/components/NewsAlert';


const Index = () => {
  const [showEnquiryModal, setShowEnquiryModal] = useState(false);

  useEffect(() => {
    // For testing purposes, let's clear the localStorage first
    localStorage.removeItem('hasVisitedBefore');
    
    // Check if user has visited before
    const hasVisited = localStorage.getItem('hasVisitedBefore');
    
    console.log('Has visited before:', hasVisited);
    console.log('Will show modal:', !hasVisited);
    
    if (!hasVisited) {
      // Show modal after a short delay for better UX
      const timer = setTimeout(() => {
        console.log('Attempting to show enquiry modal');
        setShowEnquiryModal(true);
      }, 1000); // Reduced delay to 1 second for testing
      
      return () => clearTimeout(timer);
    }
  }, []);

  const handleCloseModal = () => {
    console.log('Closing enquiry modal');
    setShowEnquiryModal(false);
    // Mark that user has visited
    localStorage.setItem('hasVisitedBefore', 'true');
    console.log('Set hasVisitedBefore to true');
  };

  console.log('Rendering Index with showEnquiryModal:', showEnquiryModal);

  return (
    <div className="min-h-screen">
      <EnquiryModal 
        isOpen={showEnquiryModal} 
        onClose={handleCloseModal} 
      />
      
      <NewsAlert />
      
      <Header />
      <Hero />
      <CoursesSection />
      <AboutLeadership />
      <AboutSection />
      <TestimonialsSection />
      <Footer />
    </div>
  );
};

export default Index;

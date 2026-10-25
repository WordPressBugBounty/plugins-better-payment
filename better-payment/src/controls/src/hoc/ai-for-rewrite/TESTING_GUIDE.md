# Rewrite with AI - Testing Guide

This guide provides comprehensive testing procedures for the "Rewrite with AI" functionality in Essential Blocks.

## Prerequisites

### 1. Environment Setup
- WordPress installation with Essential Blocks plugin active
- OpenAI API key configured in Essential Blocks AI settings
- "Rewrite with AI" feature enabled in admin settings
- Gutenberg editor environment

### 2. Required Settings
1. Navigate to **Essential Blocks > AI Suite**
2. Ensure **OpenAI API Key** is set
3. Enable **Rewrite with AI** toggle
4. Save settings

## Test Categories

### A. Admin Settings Integration

#### Test A1: Settings Panel
- [ ] Navigate to Essential Blocks > AI Suite
- [ ] Verify "Rewrite with AI" section is visible
- [ ] Toggle the "Rewrite with AI" setting on/off
- [ ] Save settings and verify they persist after page reload

#### Test A2: Setting Validation
- [ ] Disable "Rewrite with AI" in admin
- [ ] Create/edit a post with Essential Blocks
- [ ] Verify rewrite button does NOT appear in block toolbars
- [ ] Re-enable setting and verify button appears

### B. Block Toolbar Integration

#### Test B1: Button Visibility
- [ ] Create a new post/page
- [ ] Add various Essential Blocks (Button, Advanced Heading, Text, etc.)
- [ ] Select each block and verify "Rewrite with AI" button appears in toolbar
- [ ] Verify button does NOT appear for core WordPress blocks

#### Test B2: Button Functionality
- [ ] Click the "Rewrite with AI" button
- [ ] Verify popup modal opens
- [ ] Click outside modal or close button to dismiss
- [ ] Verify modal closes properly

### C. Content Processing

#### Test C1: Content Extraction
Test with different block types:

**Advanced Heading Block:**
- [ ] Add Advanced Heading with title and subtitle
- [ ] Click "Rewrite with AI"
- [ ] Verify current content is properly extracted

**Button Block:**
- [ ] Add Button with text and URL
- [ ] Click "Rewrite with AI"
- [ ] Verify button text and URL are extracted

**Text Block:**
- [ ] Add Text block with content
- [ ] Click "Rewrite with AI"
- [ ] Verify text content is extracted

#### Test C2: Schema Validation
- [ ] Use blocks with complex attributes
- [ ] Verify only content attributes are sent to AI
- [ ] Verify styling/technical attributes are preserved

### D. AI Processing

#### Test D1: Prompt Handling
- [ ] Enter custom rewrite prompt
- [ ] Select different tone options
- [ ] Click "Rewrite Now"
- [ ] Verify request is sent to backend

#### Test D2: Suggested Prompts
- [ ] Click various suggested prompt buttons
- [ ] Verify prompt is populated in textarea
- [ ] Verify textarea receives focus

#### Test D3: Response Processing
- [ ] Submit rewrite request
- [ ] Verify loading state is shown
- [ ] Wait for AI response
- [ ] Verify rewritten content is displayed
- [ ] Test "Rewrite Again" functionality
- [ ] Test "Keep" functionality

### E. Error Handling

#### Test E1: API Errors
- [ ] Use invalid/expired API key
- [ ] Attempt rewrite operation
- [ ] Verify appropriate error message is shown

#### Test E2: Network Errors
- [ ] Simulate network disconnection
- [ ] Attempt rewrite operation
- [ ] Verify timeout/network error handling

#### Test E3: Invalid Content
- [ ] Test with empty blocks
- [ ] Test with blocks containing only styling attributes
- [ ] Verify appropriate handling

### F. User Experience

#### Test F1: Loading States
- [ ] Verify loading animation during AI processing
- [ ] Verify button states during processing
- [ ] Verify user cannot submit multiple requests simultaneously

#### Test F2: Content Preview
- [ ] Verify rewritten content is properly formatted
- [ ] Test with different content types (text, JSON objects)
- [ ] Verify content preview is readable

#### Test F3: Responsive Design
- [ ] Test popup on different screen sizes
- [ ] Verify mobile responsiveness
- [ ] Test on tablet devices

### G. Integration Testing

#### Test G1: Multiple Blocks
- [ ] Add multiple Essential Blocks to a page
- [ ] Test rewrite functionality on different blocks
- [ ] Verify no interference between blocks

#### Test G2: Nested Blocks
- [ ] Test with container blocks (Row, Column)
- [ ] Verify rewrite works on nested content
- [ ] Test with Accordion and Tab blocks

#### Test G3: Existing AI Features
- [ ] Test alongside "Write with AI" functionality
- [ ] Test alongside "Generate Image" functionality
- [ ] Verify no conflicts or interference

## Performance Testing

### H. Performance Benchmarks

#### Test H1: Response Times
- [ ] Measure time from request to response
- [ ] Test with different content sizes
- [ ] Verify acceptable performance (< 30 seconds)

#### Test H2: Memory Usage
- [ ] Monitor browser memory during operation
- [ ] Test with multiple concurrent operations
- [ ] Verify no memory leaks

#### Test H3: Server Load
- [ ] Test with multiple users simultaneously
- [ ] Monitor server response times
- [ ] Verify job queue system handles load

## Security Testing

### I. Security Validation

#### Test I1: Input Sanitization
- [ ] Test with malicious input in prompts
- [ ] Verify XSS protection
- [ ] Test with special characters

#### Test I2: Permission Checks
- [ ] Test with different user roles
- [ ] Verify only authorized users can access
- [ ] Test capability requirements

#### Test I3: Nonce Verification
- [ ] Verify AJAX requests include proper nonces
- [ ] Test with expired/invalid nonces
- [ ] Verify security measures are enforced

## Browser Compatibility

### J. Cross-Browser Testing

#### Test J1: Modern Browsers
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

#### Test J2: Mobile Browsers
- [ ] Mobile Chrome
- [ ] Mobile Safari
- [ ] Mobile Firefox

## Regression Testing

### K. Existing Functionality

#### Test K1: Core Features
- [ ] Verify existing AI features still work
- [ ] Test block creation/editing
- [ ] Verify no impact on editor performance

#### Test K2: Plugin Compatibility
- [ ] Test with other popular plugins
- [ ] Verify no JavaScript conflicts
- [ ] Test with different themes

## Test Data

### Sample Content for Testing

#### Simple Text Block
```
"This is a sample text that needs to be rewritten for better engagement."
```

#### Complex Button Block
```json
{
  "buttonText": "Get Started Today",
  "buttonURL": "https://example.com",
  "buttonAlign": "center"
}
```

#### Advanced Heading Block
```json
{
  "title": "Welcome to Our Service",
  "subtitle": "Discover amazing features",
  "tagName": "h2"
}
```

## Expected Results

### Successful Test Criteria
- [ ] All toolbar buttons appear correctly
- [ ] Popup modal functions properly
- [ ] Content is extracted accurately
- [ ] AI processing completes successfully
- [ ] Rewritten content maintains structure
- [ ] Error handling works appropriately
- [ ] Performance meets benchmarks
- [ ] Security measures are effective
- [ ] Cross-browser compatibility confirmed

### Common Issues to Watch For
- Popup positioning problems
- Content structure corruption
- API timeout issues
- Memory leaks in browser
- Styling conflicts
- JavaScript errors in console

## Reporting Issues

When reporting issues, include:
1. Browser and version
2. WordPress version
3. Essential Blocks version
4. Steps to reproduce
5. Expected vs actual behavior
6. Console errors (if any)
7. Screenshots/videos

## Test Completion Checklist

- [ ] All test categories completed
- [ ] Issues documented and reported
- [ ] Performance benchmarks met
- [ ] Security validation passed
- [ ] Cross-browser testing completed
- [ ] Regression testing passed
- [ ] Documentation updated
- [ ] Feature ready for release

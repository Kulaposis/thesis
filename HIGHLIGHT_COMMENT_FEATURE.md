# Highlight and Comment Feature

## Overview
The document review system now supports a two-step workflow where users can first highlight text and then add comments specifically to those highlights. **Students can now view all highlights created by their advisers** in a read-only mode.

## How to Use

### For Advisers

#### Step 1: Highlight Text
1. Click the "Highlight" button in the document toolbar
2. Select the text you want to highlight
3. The text will be highlighted with the selected color
4. The highlight mode will automatically turn off

#### Step 2: Comment on Highlights
1. **Click on any highlighted text** to open the comment modal
2. Enter your comment about that specific highlight
3. Click "Save Comment" to save the comment linked to that highlight

### For Students

#### Viewing Adviser Highlights
1. **Students can see all highlights** created by their advisers when viewing documents
2. **Click on any highlighted text** to view details about the highlight, including:
   - Who created the highlight (adviser name)
   - When it was created
   - The highlighted text content
3. **No editing capabilities** - students can only view highlights in read-only mode

## Visual Indicators

### For Advisers

#### Regular Highlights
- **Yellow background**: Regular highlights without comments
- **Hover effect**: Shows "Click to comment" tooltip

#### Highlights with Comments
- **Blue border**: Highlights that have comments
- **Blue background gradient**: Visual distinction for commented highlights
- **Comment icon (💬)**: Small indicator in the top-right corner
- **Enhanced hover effect**: More prominent scaling effect

### For Students

#### Student View Highlights
- **Highlight background**: Same color as set by adviser
- **Blue border**: Subtle blue border to indicate read-only nature
- **Help cursor**: Cursor changes to "help" when hovering
- **Tooltip**: Shows "Click to view adviser note" on hover
- **Note icon**: Small note icon (📝) appears on hover in top-right corner

### Tooltips
- Regular highlights show the author's name
- Highlights with comments show the author's name and comment count (e.g., "Highlighted by John Doe (2 comments)")
- Student view highlights show "Highlighted by [Adviser Name]"

## Features

### In Both Regular and Fullscreen Views
- **Advisers**: Click highlights to add comments, right-click highlights to remove them
- **Students**: Click highlights to view highlight information
- Visual indicators for highlights with comments
- Automatic styling updates when comments are added

### Context Menus
- **Adviser - Left click**: Add comment to highlight
- **Adviser - Right click**: Remove highlight (with confirmation)
- **Student - Left click**: View highlight details

## User Access Control

### Advisers
- Can create, edit, and delete highlights on their assigned students' documents
- Can add comments to highlights
- Can remove highlights they created

### Students
- **Can view all highlights** created by their adviser on their own documents
- **Cannot create, edit, or delete highlights**
- **Cannot add comments to highlights**
- Can view highlight details including creation date and adviser information

## Database Structure
- Comments are linked to highlights via `highlight_id` field
- Multiple comments can be added to the same highlight
- Comments are stored with the chapter and adviser information
- **Students have read access** to highlights through the student_review.php API

## Technical Details
- Uses `window.openHighlightCommentModal()` for the comment interface (advisers only)
- **Students use `showHighlightInfo()` function** for viewing highlight details
- Comments are saved via `window.addHighlightComment()` function (advisers only)
- Visual indicators are updated via `window.updateHighlightCommentIndicators()`
- **Works in both the main document view and fullscreen mode for both user types**
- **Student highlights are loaded via `window.loadHighlights()` function**
- **Student-specific CSS classes**: `.student-view-highlight` for distinct styling

## API Endpoints

### For Advisers
- `api/document_review.php?action=get_highlights&chapter_id={id}` - Get highlights
- `api/document_review.php` (POST) - Add/remove highlights and comments

### For Students
- `api/student_review.php?action=get_highlights&chapter_id={id}` - Get highlights (read-only)
- `api/student_review.php?action=get_comments&chapter_id={id}` - Get comments (read-only)

## Workflow Example

### Adviser Workflow
1. Adviser selects text: "This methodology needs improvement"
2. Adviser clicks "Highlight" button and highlights the text
3. Adviser clicks on the highlighted text
4. Modal opens showing the highlighted text
5. Adviser enters comment: "Please add more details about the sampling method"
6. Highlight now shows blue border and comment icon

### Student Workflow
1. **Student opens their document** in the Document Review section
2. **Student sees highlighted text** created by their adviser
3. **Student clicks on the highlighted text**
4. **Modal opens showing**:
   - Adviser name and avatar
   - Highlighted text with original color
   - Creation date
5. **Student can read the highlight information** but cannot modify it
6. **Student can close the modal** and continue reviewing their document

## Implementation Notes
- **Highlights load automatically** when students view documents
- **Works with both Word documents and text content**
- **Highlights are applied after document rendering** with a small delay to ensure proper application
- **Multiple selectors are used** to find document content across different view types
- **Graceful fallback** if highlights cannot be applied to specific content areas 
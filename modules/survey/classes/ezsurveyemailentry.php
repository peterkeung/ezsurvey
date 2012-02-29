<?php
//
// Created on: <02-Apr-2004 00:00:00 Jan Kudlicka>
//
// Copyright (C) 1999-2010 eZ Systems AS. All rights reserved.
//
// This source file is part of the eZ Publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ Publish professional licences" may use this
// file in accordance with the "eZ Publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ Publish professional licence" is available at
// http://ez.no/products/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*! \file ezsurveyemailentry.php
*/

class eZSurveyEmailEntry extends eZSurveyEntry
{
    function eZSurveyEmailEntry( $row = false )
    {
        if ( !isset( $row['mandatory'] ) )
            $row['mandatory'] = 1;
        $row['type'] = 'EmailEntry';
        $this->eZSurveyEntry( $row );
    }

    function processViewActions( &$validation, $params )
    {
        $http = eZHTTPTool::instance();

        $prefix = eZSurveyType::PREFIX_ATTRIBUTE;
        $attributeID = $params['contentobjectattribute_id'];


        $postAnswer = $prefix . '_ezsurvey_answer_' . $this->ID . '_' . $attributeID;
        $answer = trim ( $http->postVariable( $postAnswer ) );
        if ( $this->attribute( 'mandatory' ) == 1 and strlen( $answer ) == 0 )
        {
            $validation['error'] = true;
            $validation['errors'][] = array( 'message' => ezpI18n::tr( 'survey', 'Please answer the question %number as well!', null,
                                                                  array( '%number' => $this->questionNumber() ) ),
                                             'question_number' => $this->questionNumber(),
                                             'code' => 'email_answer_question',
                                             'question' => $this );
        }
        else if ( strlen( $answer ) != 0 && !eZMail::validate( $answer ) )
        {
            $validation['error'] = true;
            $validation['errors'][] = array( 'message' => ezpI18n::tr( 'survey', 'Entered text in the question %number is not a valid email address!', null,
                                                                  array( '%number' => $this->questionNumber() ) ),
                                             'question_number' => $this->questionNumber(),
                                             'code' => 'email_email_not_valid',
                                             'question' => $this );
        }
        // Force the e-mail to be unique among responses
        else if ( $this->attribute( 'num' ) == 1 )
        {
            $existingEmail = eZPersistentObject::fetchObject( eZSurveyQuestionResult::definition(),
                                                              null,
                                                              array( 'questionoriginal_id' => $this->attribute( 'original_id' ),
                                                              'text' => $answer ) );

            if( $existingEmail )
            {
                $validation['error'] = true;
                $validation['errors'][] = array( 'message' => ezpI18n::tr( 'survey', 'The email address in question %number has already been entered!', null,
                                                                      array( '%number' => $this->questionNumber() ) ),
                                                 'question_number' => $this->questionNumber(),
                                                 'code' => 'email_email_not_unique',
                                                 'question' => $this );
            }
        }

        $this->setAnswer( $answer );
    }

    function processEditActions( &$validation, $params )
    {
        parent::processEditActions( $validation, $params );

        $http = eZHTTPTool::instance();

        $prefix = eZSurveyType::PREFIX_ATTRIBUTE;
        $attributeID = $params['contentobjectattribute_id'];

        $postQuestionUniqueHidden = $prefix . '_ezsurvey_question_' . $this->ID . '_num_hidden_' . $attributeID;
        if ( $http->hasPostVariable( $postQuestionUniqueHidden ) )
        {
            $postQuestionUnique = $prefix . '_ezsurvey_question_' . $this->ID . '_num_' . $attributeID;
            if ( $http->hasPostVariable( $postQuestionUnique ) )
                $newUnique = 1;
            else
                $newUnique = 0;

            if ( $newUnique != $this->attribute( 'num' ) )
                $this->setAttribute( 'num', $newUnique );
        }
    }
    
    function answer()
    {
        if ( $this->Answer !== false )
            return $this->Answer;

        $http = eZHTTPTool::instance();
        $prefix = eZSurveyType::PREFIX_ATTRIBUTE;
        $postSurveyAnswer = $prefix . '_ezsurvey_answer_' . $this->ID . '_' . $this->contentObjectAttributeID();
        if ( $http->hasPostVariable( $postSurveyAnswer ) )
        {
            $surveyAnswer = $http->postVariable( $postSurveyAnswer );
            return $surveyAnswer;
        }

        if ( $this->Text3 == 'user_email' )
        {
            $user = eZUser::currentUser();
            if ( get_class( $user ) == 'eZUser' and
                 $user->isLoggedIn() === true )
            {
                return $user->attribute( 'email' );
            }
        }
        return $this->Default;
    }
}

eZSurveyQuestion::registerQuestionType( ezpI18n::tr( 'survey', 'Email Entry' ), 'EmailEntry' );

?>

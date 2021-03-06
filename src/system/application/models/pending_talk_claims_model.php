<?php

class Pending_talk_claims_model extends Model
{
	/**
	 * Add a new claim row
	 *
	 * @param integer $talkId Talk ID
	 * @param integer $claimId Claim row ID (from talk_speaker)
	 * 
	 * @return null
	 */
	public function addClaim($talkId,$claimId)
	{	
		$data = array(
			'talk_id' 		=> $talkId,
			'claim_id'		=> $claimId,
			'speaker_id' 	=> $this->session->userdata('ID'),
			'date_added' 	=> time()
		);
		$this->db->insert('pending_talk_claims',$data);
	}
	
	/**
	 * Delete a pending claim row
	 * 
	 * @param integer $claimId Claim row ID
	 * @return null
	 */
	public function deleteClaim($claimId)
	{
		$this->db->delete('pending_talk_claims',array('ID'=>$claimId));
		return ($this->db->affected_rows()>0) ? true : false;
	}
	
	/**
	 * Get the row detail for a claim
	 *
	 * @param integer $claimId Claim ID
	 * @return array Claim row detail
	 */
	public function getClaimDetail($claimId)
	{
		return $this->db->get_where('pending_talk_claims',array('ID'=>$claimId))->result();
	}
	
	public function approveClaim($claimId)
	{
		$claimDetail 		= $this->getClaimDetail($claimId);
		if(!isset($claimDetail[0])){
			return false;
		}
		$talkSpeakerData 	= array(
			'speaker_id' => $claimDetail[0]->speaker_id
		);
		
		$this->db->where('id',$claimDetail[0]->claim_id);
		$this->db->update('talk_speaker',$talkSpeakerData);
		
		// remove the claim row
		$this->db->delete('pending_talk_claims',array('ID'=>$claimId));
		return true;
	}
	
	/**
	 * Given the event ID, find the claims for the talks in the event
	 * 
	 * @param integer $eventId Event ID
	 * @return array $result Pending claims found
	 */
	public function getEventTalkClaims($eventId)
	{
		$CI=&get_instance();
		$CI->load->model('event_model','eventModel');
		
		$eventTalks = $CI->eventModel->getEventTalks($eventId);
		if(empty($eventTalks)){
			return array();
		}
		
		$talkIds = array();
		foreach($eventTalks as $talk){ $talkIds[] = $talk->ID; }
		
		$results = $this->db->select('*,pending_talk_claims.id as pending_claim_id')
			->from('pending_talk_claims')
			->join('talks','pending_talk_claims.talk_id = talks.id')
			->join('user','pending_talk_claims.speaker_id = user.id')
			->where_in('pending_talk_claims.talk_id',$talkIds)
			->get()->result();
			
		
		foreach($results as &$result){
			$result->claim_detail 	= $this->db->get_where('talk_speaker',array('ID'=>$result->claim_id))->result();
		}
		
		return $results;
	}
	
}

?>
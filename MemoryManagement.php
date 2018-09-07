<?php
/**
*该类封装了FIFO和LRU调度功能函数
*/
class MemoryManagement
{
    //用于储存指令，模拟指令在辅存中的分布情况
    private $InstructionSet;
    //FIFO调度算法的内存块数组
    private $FIFOMemoryBlock;
    //LRU调度算法的内存块数组
    private $LRUMemoryBlock;
    //FIFO调度算法的缺页记录
    private $FIFOPageFault;
    //LRU调度算法的缺页记录
    private $LRUPageFault;
    //内存块数量
    private $MemoryBlockNum;
    //页面指令条数
    private $PageInstructionNum;

    function __construct(){
        $this->InstructionSet = array();
        //数组中存储的为初始条件下每条指令的索引值
        for($i = 0; $i < 320; $i++){
            $this->InstructionSet[$i] = $i;
        }
        $this->FIFOMemoryBlock = array();
        $this->LRUMemoryBlock = array();
        $this->FIFOPageFault = array();
        $this->LRUPageFault = array();
        //接收前端传来的数据
        $this->MemoryBlockNum = $_POST["MemoryBlockNum"];
        $this->PageInstructionNum = $_POST["PageInstructionNum"];
    }

    public function GetInstructionExecutionResult(){
        $this->MemoryManagement();
        $RetArray = array('FIFOPageFault' => array(), 'FIFOPageFaultRatio' => 0.0, 'LRUPageFault' => array(), 'LRUPageFaultRatio' => 0.0);
        $RetArray['FIFOPageFault'] = $this->FIFOPageFault;
        //计算FIFO算法的缺页率
        $RetArray['FIFOPageFaultRatio'] = (float)count($this->FIFOPageFault) / 320;
        $RetArray['LRUPageFault'] = $this->LRUPageFault;
        //计算LRU算法的缺页率
        $RetArray['LRUPageFaultRatio'] = (float)count($this->LRUPageFault) / 320;
        echo json_encode($RetArray);
    }

    //该函数负责模拟指令的执行顺序并调用相应的调度算法进行处理
    private function MemoryManagement(){
        $InstructionSet = $this->InstructionSet;
        $InstructionPointer = mt_rand(0, 319);
        //顺序执行两条指令
        $this->SequentialInstruction($InstructionSet, $InstructionPointer);
        while (!empty($InstructionSet)) {
            if($InstructionPointer > 0){
                //跳转到前面顺序执行两条指令
                $InstructionPointer = mt_rand(0, $InstructionPointer - 1);
                $this->SequentialInstruction($InstructionSet, $InstructionPointer);

            }
            if($InstructionPointer <= count($InstructionSet) - 1){
                //跳转到后面顺序执行两条指令
                $InstructionPointer = mt_rand($InstructionPointer, count($InstructionSet) - 1);
                $this->SequentialInstruction($InstructionSet, $InstructionPointer);
            }
        }
    }

    //顺序执行指令函数
    private function SequentialInstruction(&$InstructionSet, &$InstructionPointer){
        for($i = $InstructionPointer; $i - $InstructionPointer <= 1 && $i < count($InstructionSet); $i++){
            $this->PageSearch($this->FIFOMemoryBlock, $this->FIFOPageFault, floor($InstructionSet[$i] / $this->PageInstructionNum) + 1, FALSE);
            $this->PageSearch($this->LRUMemoryBlock, $this->LRUPageFault, floor($InstructionSet[$i] / $this->PageInstructionNum) + 1, TRUE);
        }
        array_splice($InstructionSet, $InstructionPointer, $i - $InstructionPointer);
    }

    //页面调度函数
    private function PageSearch(&$MemoryBlock, &$PageFault, $PageNum, $Tag){
        for($i = 0; $i < count($MemoryBlock); $i++){
            //如果目标页面在内存块中
            if($MemoryBlock[$i] == $PageNum){
                if($Tag){
                    //此处是LRU算法的调度过程
                    $Temp = $MemoryBlock[$i];
                    array_splice($MemoryBlock, $i, 1);
                    $MemoryBlock[count($MemoryBlock)] = $Temp;
                }
                return;
            }
        }
        //如果目标页面不在内存块中
        $PageFault[count($PageFault)] = $PageNum;
        if(count($MemoryBlock) == $this->MemoryBlockNum){
            array_splice($MemoryBlock, 0, 1);
            $MemoryBlock[$this->MemoryBlockNum - 1] = $PageNum;
        }else{
            $MemoryBlock[count($MemoryBlock)] = $PageNum;
        }
    }
}

//实例化MemoryManagement类
$MemoryManagementObj = new MemoryManagement();
$MemoryManagementObj->GetInstructionExecutionResult();
?>
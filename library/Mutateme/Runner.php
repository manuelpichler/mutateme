<?php

require_once 'Mutateme/Generator.php';

require_once 'Mutateme/Runkit.php';

require_once 'Mutateme/Adapter/Phpunit.php';

class Mutateme_Runner
{
    protected $_srcDirectory = '';

    protected $_specDirectory = '';

    protected $_adapterName = '';

    protected $_adapter = null;

    protected $_srcFiles = array();

    protected $_mutables = array();

    protected $_generator = null;

    protected $_runkit = null;

    public function execute()
    {
        // use adapter to ensure all tests are clean

        // process mutants
        $mutables = $this->getMutables();
        foreach ($mutables as $mutable) {
            $mutations = $mutable->getMutations();
            foreach ($mutations as $mutation) {
                $this->getRunkit()->apply($mutation);
                // do testing and process results using an adapter
                $this->getRunkit()->reverse($mutation);
            }
        }
    }

    public function getFiles()
    {
        if (empty($this->_srcFiles)) {
            $this->_srcFiles = $this->getGenerator()->getFiles();
        }
        return $this->_srcFiles;
    }

    public function setGenerator(Mutateme_Generator $generator)
    {
        $this->_generator = $generator;
        $this->_generator->setSourceDirectory($this->getSourceDirectory());
    }

    public function getGenerator()
    {
        if (!isset($this->_generator)) {
            $this->_generator = new Mutateme_Generator($this);
            $this->_generator->setSourceDirectory($this->getSourceDirectory());
        }
        return $this->_generator;
    }

    public function setSourceDirectory($srcDirectory)
    {
        $srcDirectory = rtrim($srcDirectory, ' \\/');
        if (!is_dir($srcDirectory) || !is_readable($srcDirectory)) {
            throw new Exception('Invalid source directory: "'.$srcDirectory.'"');
        }
        $this->_srcDirectory = $srcDirectory;
    }

    public function getSourceDirectory()
    {
        return $this->_srcDirectory;
    }

    public function setSpecDirectory($specDirectory)
    {
        $specDirectory = rtrim($specDirectory, ' \\/');
        if (!is_dir($specDirectory) || !is_readable($specDirectory)) {
            throw new Exception('Invalid source directory: "'.$specDirectory.'"');
        }
        $this->_specDirectory = $specDirectory;
    }

    public function getSpecDirectory()
    {
        return $this->_specDirectory;
    }

    public function setAdapterName($adapter)
    {
        $this->_adapterName = $adapter;
    }

    public function getAdapterName()
    {
        return $this->_adapterName;
    }

    public function getMutables()
    {
        if (empty($this->_mutables)) {
            $generator = $this->getGenerator();
            $generator->generate();
            $this->_mutables = $generator->getMutables();
        }
        return $this->_mutables;
    }

    public function getAdapter()
    {
        if (is_null($this->_adapter)) {
            $name = ucfirst(strtolower($this->getAdapterName()));
            $class = 'Mutateme_Adapter_' . $name;
            if (!class_exists($class)) {
                throw new Exception('Invalid Adapter: ' . strtolower($name));
            }
            $this->_adapter = new $class;
        }
        return $this->_adapter;
    }

    public function setAdapter(Mutateme_Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }

    public function getRunkit()
    {
        if (is_null($this->_runkit)) {
            $this->_runkit = new Mutateme_Runkit;
        }
        return $this->_runkit;
    }

}




class Mutateme_Runnerx
{

    protected $_sourceDirectory = null;
    protected $_specDirectory = null;
    protected $_adapterName = null;
    protected $_files = array();
    protected $_mutables = null;

    public function execute()
    {
        $this->prepare();

        /****************************************@@
         * Refactor Red Alert For All Code Below :)
         */

        $countMutants = 0;
        $countMutantsKilled = 0;
        $countMutantsEscaped = 0;
        $diffMutantsEscaped = array();

        // ensure all specs or tests are clean!
        $adapterClass = 'Mutateme_Adapter_'
            . ucfirst(strtolower($this->getAdapterName()));
        $adapter = new $adapterClass($this);
        $result = $adapter->execute();
        if (!$result) {
            $str = 'Before you face the Mutants, you first need a 100% pass rate!' . PHP_EOL;
            $str .=  $adapter->getOutput();
            return $str;
        }

        // MUTANTS!!!
        foreach ($this->_mutables as $mutable) {
            $file = $mutable->getFilename();
            $originalFileContent = file_get_contents($file);
            $mutations = $mutable->getMutations();
            foreach ($mutations as $tokenIndex=>$mutation) {
                $mutatedFileContent = $mutation->mutate($originalFileContent, $tokenIndex);

                //file_put_contents($file, $mutatedFileContent);
                require_once $file;


                $result = $adapter->execute();

                // result collation
                $countMutants++;
                if ($result) { // careful - we want a FALSE result!
                    $countMutantsKilled++;
                } else { // tests all passing is a BAD thing :)
                    $countMutantsEscaped++;
                    $diffMutantsEscaped[] = $mutation->getDiff();
                }

                // revert to original state for next mutation
                file_put_contents($file, $originalFileContent);

                // small progress echo
                echo '.';
            }
        }

        // reporting
        $report = PHP_EOL;
        $report .= $countMutants;
        $report .= $countMutants == 1 ? ' Mutant' : ' Mutants';
        $report .= ' born out of the mutagenic slime!';
        $report .= PHP_EOL . PHP_EOL;
        $report .= $countMutantsKilled;
        $report .= $countMutantsKilled == 1 ? ' Mutant' : ' Mutants';
        $report .= ' exterminated!';
        $report .= PHP_EOL . PHP_EOL;
        if ($countMutantsEscaped > 0) {
            $report .= $countMutantsEscaped;
            $report .= $countMutantsEscaped == 1 ? ' Mutant' : ' Mutants';
            $report .= ' escaped; the integrity of your suite may be compromised by the following Mutants:';
            $report .= PHP_EOL . PHP_EOL;

            $i = 1;
            foreach ($diffMutantsEscaped as $mutantDiff) {
                $report .= $i . ') ' . PHP_EOL . $mutantDiff;
                $report .= PHP_EOL . PHP_EOL;
                $i++;
            }

            $report .= 'Happy Hunting! Remember that some Mutants may just be Ghosts (or if you want to be boring, false positives).';
        } else {
            $report .= 'No Mutants survived! Muahahahaha!';
        }

        return $report;
    }

    public function setSourceDirectory($path)
    {
        $path = rtrim($path, ' \\/');
        $this->_sourceDirectory = $path;
    }

    public function setSpecDirectory($path)
    {
        $path = rtrim($path, ' \\/');
        $this->_specDirectory = $path;
    }

    public function setAdapterName($adapter)
    {
        $this->_adapterName = $adapter;
    }

    public function getSourceDirectory()
    {
        return $this->_sourceDirectory;
    }

    public function getSpecDirectory()
    {
        return $this->_specDirectory;
    }

    public function getAdapterName()
    {
        return $this->_adapterName;
    }

    public function setGenerator(Mutateme_Generator $generator)
    {
        $this->_generator = $generator;
    }

    public function getGenerator()
    {
        if (!isset($this->_generator)) {
            $this->_generator = new Mutateme_Generator($this);
        }
        return $this->_generator;
    }

    public function getFiles()
    {
        return $this->_files;
    }

    public function prepare()
    {
        $this->_collateFiles($this->getSourceDirectory());
    }

    public function getMutables()
    {
        return $this->_mutables;
    }

    protected function _collateFiles($target)
    {
        $d = dir($target);
        while (FALSE !== ($res = $d->read())) {
            if ($res == '.' || $res == '..') {
                continue;
            }
            $entry = $target . '/' . $res;
            if (is_dir($entry)) {
                $this->_collateFiles($entry);
                continue;
            }
            $this->_files[] = $entry;
        }
        $d->close();
    }

    protected function _generateMutations()
    {
        $generator = $this->getGenerator();
        $generator->generate();
        $this->_mutables = $generator->getMutables();
    }
}